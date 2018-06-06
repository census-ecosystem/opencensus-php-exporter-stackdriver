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

namespace OpenCensus\Trace\Exporter;

use Google\Cloud\Core\Batch\BatchRunner;
use Google\Cloud\Core\Batch\BatchTrait;
use Google\Cloud\Trace\TraceClient;
use Google\Cloud\Trace\Span;
use Google\Cloud\Trace\Trace;
use OpenCensus\Trace\SpanData;
use OpenCensus\Trace\Exporter\Stackdriver\SpanConverter;

/**
 * This implementation of the ExporterInterface use the BatchRunner to provide
 * reporting of Traces and their Spans to Google Cloud Stackdriver Trace.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Tracer;
 * use OpenCensus\Trace\Exporter\StackdriverExporter;
 *
 * $reporter = new StackdriverExporter([
 *   'clientConfig' => [
 *      'projectId' => 'my-project'
 *   ]
 * ]);
 * Tracer::start($reporter);
 * ```
 *
 * The above configuration will synchronously report the traces to Google Cloud
 * Stackdriver Trace. You can enable an experimental asynchronous reporting
 * mechanism using
 * <a href="https://github.com/GoogleCloudPlatform/google-cloud-php/tree/master/src/Core/Batch">BatchDaemon</a>.
 * To enable asynchronous exporting, set the
 * `IS_BATCH_DAEMON_RUNNING` environment variable to `true`.
 */
class StackdriverExporter implements ExporterInterface
{
    const VERSION = '0.1.0';

    use BatchTrait;

    /**
     * @var TraceClient
     */
    private static $client;

    /**
     * @var array
     */
    private $clientConfig;

    /**
     * Create a TraceExporter that utilizes background batching.
     *
     * @param array $options [optional] Configuration options.
     *
     *     @type TraceClient $client A trace client used to instantiate traces
     *           to be delivered to the batch queue.
     *     @type bool $debugOutput Whether or not to output debug information.
     *           Please note debug output currently only applies in CLI based
     *           applications. **Defaults to** `false`.
     *     @type array $batchOptions A set of options for a BatchJob. See
     *           <a href="https://github.com/GoogleCloudPlatform/google-cloud-php/blob/master/src/Core/Batch/BatchJob.php">\Google\Cloud\Core\Batch\BatchJob::__construct()</a>
     *           for more details.
     *           **Defaults to** ['batchSize' => 1000,
     *                            'callPeriod' => 2.0,
     *                            'workerNum' => 2].
     *     @type array $clientConfig Configuration options for the Trace client
     *           used to handle processing of batch items.
     *           For valid options please see
     *           <a href="https://github.com/GoogleCloudPlatform/google-cloud-php/blob/master/src/Trace/TraceClient.php">\Google\Cloud\Trace\TraceClient::__construct()</a>.
     *     @type BatchRunner $batchRunner A BatchRunner object. Mainly used for
     *           the tests to inject a mock. **Defaults to** a newly created
     *           BatchRunner.
     *     @type string $identifier An identifier for the batch job.
     *           **Defaults to** `stackdriver-trace`.
     */
    public function __construct(array $options = [])
    {
        $options += [
            'async' => false,
            'client' => null
        ];
        $this->setCommonBatchProperties($options + [
            'identifier' => 'stackdriver-trace',
            'batchMethod' => 'insertBatch'
        ]);
        self::$client = $options['client'] ?: new TraceClient($this->clientConfig);
    }

    /**
     * Report the provided Trace to a backend.
     *
     * @param SpanData[] $spans
     * @return bool
     */
    public function export(array $spans)
    {
        if (empty($spans)) {
            return false;
        }

        // Pull the traceId from the first span
        $spans = array_map([SpanConverter::class, 'convertSpan'], $spans);
        $rootSpan = $spans[0];
        $trace = self::$client->trace(
            $rootSpan->traceId()
        );

        // build a Trace object and assign Spans
        $trace->setSpans($spans);

        try {
            return $this->batchRunner->submitItem($this->identifier, $trace);
        } catch (\Exception $e) {
            error_log('Reporting the Trace data failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Returns an array representation of a callback which will be used to write
     * batch items.
     *
     * @return array
     */
    protected function getCallback()
    {
        if (!isset(self::$client)) {
            self::$client = new TraceClient($this->clientConfig);
        }

        return [self::$client, $this->batchMethod];
    }
}
