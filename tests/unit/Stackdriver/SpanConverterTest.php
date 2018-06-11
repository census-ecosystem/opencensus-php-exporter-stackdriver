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

namespace OpenCensus\Tests\Unit\Trace\Exporter\Stackdriver;

use OpenCensus\Trace\Exporter\Stackdriver\SpanConverter;
use OpenCensus\Trace\Annotation as OCAnnotation;
use OpenCensus\Trace\Link as OCLink;
use OpenCensus\Trace\MessageEvent as OCMessageEvent;
use OpenCensus\Trace\Span as OCSpan;
use OpenCensus\Trace\Status as OCStatus;
use Prophecy\Argument;
use Google\Cloud\Trace\Link;
use Google\Cloud\Trace\MessageEvent;
use Google\Cloud\Trace\Span;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class SpanConverterTest extends TestCase
{
    const TIMESTAMP_FORMAT_REGEXP = '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{9}Z/';

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
        $this->assertNull($span->parentSpanId());
        $this->assertRegExp(self::TIMESTAMP_FORMAT_REGEXP, $span->info()['startTime']);
        $this->assertRegExp(self::TIMESTAMP_FORMAT_REGEXP, $span->info()['endTime']);
    }

    public function testSetSpanIds()
    {
        $span = new OCSpan([
            'name' => 'span',
            'spanId' => 'aaa',
            'parentSpanId' => 'bbb',
            'startTime' => microtime(true),
            'endTime' => microtime(true) + 10
        ]);
        $span = SpanConverter::convertSpan($span->spanData());
        $this->assertEquals('0000000000000aaa', $span->spanId());
        $this->assertEquals('0000000000000bbb', $span->parentSpanId());
    }

    public function testFormatsStackTrace()
    {
        $span = new OCSpan([
            'stackTrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            'startTime' => microtime(true),
            'endTime' => microtime(true) + 10
        ]);

        $spans = SpanConverter::convertSpan($span->spanData());

        $data = $spans->info();
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

        $attributes = $span->info()['attributes']['attributeMap'];
        $this->assertArrayHasKey($expectedAttributeKey, $attributes);
        $this->assertEquals($expectedAttributeValue, $attributes[$expectedAttributeKey]['stringValue']['value']);
    }

    public function attributesToTest()
    {
        return [
            ['http.host', 'foo.example.com', '/http/host', 'foo.example.com'],
            ['http.port', '80', '/http/port', '80'],
            ['http.path', '/foobar', '/http/url', '/foobar'],
            ['http.method', 'PUT', '/http/method', 'PUT'],
            ['http.user_agent', 'user agent', '/http/user_agent', 'user agent'],
            ['random.key', 'some value', 'random.key', 'some value']
        ];
    }

    public function testLinks()
    {
        $span = new OCSpan([
            'startTime' => microtime(true),
            'endTime' => microtime(true) + 10,
            'links' => [
                new OCLink('traceid', 'spanid', [
                    'type' => OCLink::TYPE_CHILD_LINKED_SPAN,
                    'attributes' => [
                        'foo' => 'bar'
                    ]
                ])
            ]
        ]);
        $span = SpanConverter::convertSpan($span->spanData());
        $data = $span->info()['links']['link'];
        $this->assertCount(1, $data);
        $link = $data[0];
        $this->assertEquals('traceid', $link['traceId']);
        $this->assertEquals('spanid', $link['spanId']);
        $this->assertEquals(Link::TYPE_CHILD_LINKED_SPAN, $link['type']);
        $this->assertEquals('bar', $link['attributes']['attributeMap']['foo']['stringValue']['value']);
    }

    public function testTimeEvents()
    {
        $time = 1490737450.484299;
        $span = new OCSpan([
            'traceId' => 'aaa',
            'timeEvents' => [
                new OCAnnotation('some-description', [
                    'attributes' => ['foo' => 'bar'],
                    'time' => $time
                ]),
                new OCMessageEvent(OCMessageEvent::TYPE_SENT, 'message-id', [
                    'uncompressedSize' => 234,
                    'compressedSize' => 123,
                    'time' => $time
                ])
            ],
            'startTime' => new \DateTime(),
            'endTime' => new \DateTime(),
            'stackTrace' => []
        ]);
        $span = SpanConverter::convertSpan($span->spanData());

        $timeEvents = $span->info()['timeEvents']['timeEvent'];
        $this->assertCount(2, $timeEvents);
        $event1 = $timeEvents[0];
        $this->assertEquals('some-description', $event1['annotation']['description']['value']);
        $this->assertRegExp(self::TIMESTAMP_FORMAT_REGEXP, $event1['time']);
        $this->assertEquals('2017-03-28T21:44:10.484299000Z', $event1['time']);

        $event2 = $timeEvents[1];
        $this->assertEquals(MessageEvent::TYPE_SENT, $event2['messageEvent']['type']);
        $this->assertEquals('message-id', $event2['messageEvent']['id']);
        $this->assertEquals(234, $event2['messageEvent']['uncompressedSizeBytes']);
        $this->assertEquals(123, $event2['messageEvent']['compressedSizeBytes']);
        $this->assertRegExp(self::TIMESTAMP_FORMAT_REGEXP, $event2['time']);
        $this->assertEquals('2017-03-28T21:44:10.484299000Z', $event2['time']);
    }

    public function testStatus()
    {
        $span = new OCSpan([
            'startTime' => microtime(true),
            'endTime' => microtime(true) + 10,
            'status' => new OCStatus(
                200,
                'OK'
            )
        ]);
        $span = SpanConverter::convertSpan($span->spanData());
        $data = $span->info();
        $this->assertEquals(200, $data['status']['code']);
        $this->assertEquals('OK', $data['status']['message']);
    }

    public function testEmptyStatus()
    {
        $span = new OCSpan([
            'startTime' => microtime(true),
            'endTime' => microtime(true) + 10
        ]);
        $span = SpanConverter::convertSpan($span->spanData());
        $data = $span->info();
        $this->assertTrue(!isset($data['status']));
    }
}
