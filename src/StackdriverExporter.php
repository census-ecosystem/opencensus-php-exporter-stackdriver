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

use OpenCensus\Trace\Tracer\TracerInterface;

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
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Tracer;
 * use OpenCensus\Trace\Exporter\StackdriverExporter;
 *
 * $reporter = new StackdriverExporter([
 *   'async' => true,
 *   'clientConfig' => [
 *      'projectId' => 'my-project'
 *   ]
 * ]);
 * Tracer::start($reporter);
 * ```
 *
 * Note that to use the `async` option, you will also need to set the
 * `IS_BATCH_DAEMON_RUNNING` environment variable to `true`.
 *
 * @experimental The experimental flag means that while we believe this method
 *      or class is ready for use, it may change before release in backwards-
 *      incompatible ways. Please use with caution, and test thoroughly when
 *      upgrading.
 */
class StackdriverExporter implements ExporterInterface
{
    /**
     * Report the provided Trace to a backend.
     *
     * @param  TracerInterface $tracer
     * @return bool
     */
    public function report(TracerInterface $tracer)
    {
        // TODO: Implement this
        return false;
    }
}
