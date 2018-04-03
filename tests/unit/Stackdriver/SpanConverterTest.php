<?php
/**
 * Copyright 2017 OpenCensus Authors
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

namespace OpenCensus\Tests\Unit\Trace\Exporter\Stackdriver;

use OpenCensus\Trace\Exporter\Stackdriver\SpanConverter;
use OpenCensus\Trace\Span as OCSpan;
use Prophecy\Argument;
use Google\Cloud\Trace\Span;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class SpanConverterTest extends TestCase
{
    /**
     * @var SpanData[]
     */
    private $spans;

    public function setUp()
    {
        parent::setUp();
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

    public function testFormatsTrace()
    {
        $span = new OCSpan([
            'name' => 'span',
            'startTime' => microtime(true),
            'endTime' => microtime(true) + 10
        ]);
        $span = SpanConverter::convertSpan($span->spanData());

        $this->assertInstanceOf(Span::class, $span);
        $this->assertInternalType('string', $span->name());
        $this->assertInternalType('string', $span->spanId());
        $this->assertRegExp('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{9}Z/', $span->jsonSerialize()['startTime']);
        $this->assertRegExp('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{9}Z/', $span->jsonSerialize()['endTime']);
    }

    public function testFormatsStackTrace()
    {
        $span = new OCSpan([
            'stackTrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            'startTime' => microtime(true),
            'endTime' => microtime(true) + 10
        ]);

        $spans = SpanConverter::convertSpan($span->spanData());

        $data = $spans->jsonSerialize();
        $this->assertArrayHasKey('stackTrace', $data);
    }

    /**
     * @dataProvider attributesToTest
     */
    public function testMapsAttributes($key, $value, $expectedAttributeKey, $expectedAttributeValue)
    {
        $span = new OCSpan([
            'attributes' => [
                $key => $value
            ],
            'startTime' => microtime(true),
            'endTime' => microtime(true) + 10
        ]);

        $span = SpanConverter::convertSpan($span->spanData());

        $attributes = $span->jsonSerialize()['attributes'];
        $this->assertArrayHasKey($expectedAttributeKey, $attributes);
        $this->assertEquals($expectedAttributeValue, $attributes[$expectedAttributeKey]);
    }

    public function attributesToTest()
    {
        return [
            ['http.host', 'foo.example.com', '/http/host', 'foo.example.com'],
            ['http.port', '80', '/http/port', '80'],
            ['http.path', '/foobar', '/http/url', '/foobar'],
            ['http.method', 'PUT', '/http/method', 'PUT'],
            ['http.user_agent', 'user agent', '/http/user_agent', 'user agent']
        ];
    }
}
