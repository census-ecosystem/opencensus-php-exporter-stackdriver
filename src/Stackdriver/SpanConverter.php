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

use Google\Cloud\Trace\Span;
use OpenCensus\Trace\Span as OCSpan;
use OpenCensus\Trace\SpanData;

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
        return new Span($span->traceId(), [
            'name' => $span->name(),
            'startTime' => $span->startTime(),
            'endTime' => $span->endTime(),
            'spanId' => $span->spanId(),
            'parentSpanId' => $span->parentSpanId(),
            'attributes' => self::convertAttributes($span->attributes()),
            'stackTrace' => $span->stackTrace()
        ]);
    }

    private static function convertAttributes(array $attributes)
    {
        $newAttributes = [];
        foreach ($attributes as $key => $value) {
            if (array_key_exists($key, self::ATTRIBUTE_MAP)) {
                $newAttributes[self::ATTRIBUTE_MAP[$key]] = $value;
            } else {
                $newAttributes[$key] = $value;
            }
        }
        return $newAttributes;
    }
}