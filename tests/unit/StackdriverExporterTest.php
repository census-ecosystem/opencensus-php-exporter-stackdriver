<?php
/**
 * Copyright 2018 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Tests\Unit\Trace\Exporter;

require_once __DIR__ . '/mock_error_log.php';

use OpenCensus\Trace\Exporter\StackdriverExporter;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Tracer\TracerInterface;
use OpenCensus\Trace\Tracer\ContextTracer;
use OpenCensus\Trace\Span as OCSpan;
use Prophecy\Argument;
use Google\Cloud\Core\Batch\BatchRunner;
use Google\Cloud\Trace\Trace;
use Google\Cloud\Trace\Span;
use Google\Cloud\Trace\TraceClient;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class StackdriverExporterTest extends TestCase
{
    /**
     * @var TraceClient
     */
    private $client;

    /**
     * @var SpanData[]
     */
    private $spans;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->prophesize(TraceClient::class);

        $this->spans = array_map(function ($span) {
            return $span->spanData();
        }, [
            new OCSpan([
                'name' => 'span',
                'startTime' => microtime(true),
                'endTime' => microtime(true) + 10
            ])
        ]);
    }

    public function testReportWithAnExceptionErrorLog()
    {
        $trace = $this->prophesize(Trace::class);
        $trace->setSpans(Argument::any())->shouldBeCalled();
        $this->client->trace(Argument::any())->willReturn($trace->reveal());

        $batchRunner = $this->prophesize(BatchRunner::class);
        $batchRunner->registerJob(Argument::any(), Argument::any(), Argument::any())->shouldBeCalled();
        $batchRunner->submitItem(Argument::any(), Argument::any())->willThrow(
            new \Exception('error_log test')
        )->shouldBeCalled();

        $exporter = new StackdriverExporter([
            'client' => $this->client->reveal(),
            'batchRunner' => $batchRunner->reveal()
        ]);
        $this->expectOutputString(
            'Reporting the Trace data failed: error_log test'
        );
        $this->assertFalse($exporter->export($this->spans));
    }

    public function testEmptyTrace()
    {
        $exporter = new StackdriverExporter(['client' => $this->client->reveal()]);
        $this->assertFalse($exporter->export([]));
    }

    public function testReportsVersionAttribute()
    {
        $trace = $this->prophesize(Trace::class);
        $trace->setSpans(Argument::that(function ($spans) {
            $this->assertCount(1, $spans);
            $attributes = $spans[0]->jsonSerialize()['attributes'];
            $this->assertArrayHasKey('g.co/agent', $attributes);
            $this->assertRegexp('/\d+\.\d+\.\d+/', $attributes['g.co/agent']);
            return true;
        }))->shouldBeCalled();
        $this->client->trace('aaa')->willReturn($trace->reveal());

        $span = new OCSpan([
            'traceId' => 'aaa'
        ]);
        $span->setStartTime();
        $span->setEndTime();

        $batchRunner = $this->prophesize(BatchRunner::class);
        $batchRunner->registerJob(Argument::any(), Argument::any(), Argument::any())->shouldBeCalled();
        $batchRunner->submitItem(Argument::any(), Argument::any())
            ->willReturn(true)
            ->shouldBeCalled();
        $exporter = new StackdriverExporter([
            'client' => $this->client->reveal(),
            'batchRunner' => $batchRunner->reveal()
        ]);
        $this->assertTrue($exporter->export([$span->spanData()]));
    }
}
