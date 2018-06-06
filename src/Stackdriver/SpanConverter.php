<?php
/**
 * Copyright 2018 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Trace\Exporter\Stackdriver;

use Google\Cloud\Trace\Annotation;
use Google\Cloud\Trace\Link;
use Google\Cloud\Trace\MessageEvent;
use Google\Cloud\Trace\Span;
use Google\Cloud\Trace\Status;
use OpenCensus\Trace\Annotation as OCAnnotation;
use OpenCensus\Trace\Link as OCLink;
use OpenCensus\Trace\MessageEvent as OCMessageEvent;
use OpenCensus\Trace\Span as OCSpan;
use OpenCensus\Trace\Status as OCStatus;
use OpenCensus\Trace\SpanData;
use OpenCensus\Trace\Exporter\StackdriverExporter;
use OpenCensus\Version;

/**
 * This class handles converting from the OpenCensus data model into its
 * Stackdriver Trace representation.
 */
class SpanConverter
{
    const ATTRIBUTE_MAP = [
        OCSpan::ATTRIBUTE_HOST => '/http/host',
        OCSpan::ATTRIBUTE_PORT => '/http/port',
        OCSpan::ATTRIBUTE_METHOD => '/http/method',
        OCSpan::ATTRIBUTE_PATH => '/http/url',
        OCSpan::ATTRIBUTE_USER_AGENT => '/http/user_agent',
        OCSpan::ATTRIBUTE_STATUS_CODE => '/http/status_code'
    ];
    const LINK_TYPE_MAP = [
        OCLink::TYPE_UNSPECIFIED => Link::TYPE_UNSPECIFIED,
        OCLink::TYPE_CHILD_LINKED_SPAN => Link::TYPE_CHILD_LINKED_SPAN,
        OCLink::TYPE_PARENT_LINKED_SPAN => Link::TYPE_PARENT_LINKED_SPAN
    ];
    const MESSAGE_TYPE_MAP = [
        OCMessageEvent::TYPE_UNSPECIFIED => MessageEvent::TYPE_UNSPECIFIED,
        OCMessageEvent::TYPE_SENT => MessageEvent::TYPE_SENT,
        OCMessageEvent::TYPE_RECEIVED => MessageEvent::TYPE_RECEIVED
    ];

    const AGENT_KEY = 'g.co/agent';
    const AGENT_STRING = 'opencensus-php [' . Version::VERSION . '] php-stackdriver-exporter [' .
        StackdriverExporter::VERSION . ']';

    /**
     * Convert an OpenCensus SpanData to its Stackdriver Trace representation.
     *
     * @access private
     *
     * @param SpanData $span The span to convert.
     * @return Span
     */
    public static function convertSpan(SpanData $span)
    {
        $spanOptions = [
            'name' => $span->name(),
            'startTime' => $span->startTime(),
            'endTime' => $span->endTime(),
            'spanId' => $span->spanId(),
            'attributes' => self::convertAttributes($span->attributes()),
            'stackTrace' => $span->stackTrace(),
            'links' => self::convertLinks($span->links()),
            'timeEvents' => self::convertTimeEvents($span->timeEvents()),
            'status' => self::convertStatus($span->status())
        ];
        if ($span->parentSpanId()) {
            $spanOptions['parentSpanId'] = $span->parentSpanId();
        }
        return new Span($span->traceId(), $spanOptions);
    }

    private static function convertAttributes(array $attributes)
    {
        $newAttributes = [
            self::AGENT_KEY => self::AGENT_STRING
        ];
        foreach ($attributes as $key => $value) {
            if (array_key_exists($key, self::ATTRIBUTE_MAP)) {
                $newAttributes[self::ATTRIBUTE_MAP[$key]] = $value;
            } else {
                $newAttributes[$key] = $value;
            }
        }
        return $newAttributes;
    }

    private static function convertLinks(array $links)
    {
        return array_map(function (OCLink $link) {
            return new Link($link->traceId(), $link->spanId(), [
                'type' => self::LINK_TYPE_MAP[$link->type()],
                'attributes' => $link->attributes()
            ]);
        }, $links);
    }

    private static function convertTimeEvents(array $events)
    {
        $newEvents = [];
        foreach ($events as $event) {
            if ($event instanceof OCAnnotation) {
                $newEvents[] = self::convertAnnotation($event);
            } elseif ($event instanceof OCMessageEvent) {
                $newEvents[] = self::convertMessageEvent($event);
            }
        }
        return $newEvents;
    }

    private static function convertAnnotation(OCAnnotation $annotation)
    {
        return new Annotation($annotation->description(), [
            'attributes' => $annotation->attributes(),
            'time' => $annotation->time()
        ]);
    }

    private static function convertMessageEvent(OCMessageEvent $messageEvent)
    {
        return new MessageEvent($messageEvent->id(), [
            'type' => self::MESSAGE_TYPE_MAP[$messageEvent->type()],
            'uncompressedSizeBytes' => $messageEvent->uncompressedSize(),
            'compressedSizeBytes' => $messageEvent->compressedSize(),
            'time' => $messageEvent->time()
        ]);
    }

    private static function convertStatus(OCStatus $status = null)
    {
        if ($status) {
            return new Status($status->code(), $status->message());
        } else {
            return null;
        }
    }
}
