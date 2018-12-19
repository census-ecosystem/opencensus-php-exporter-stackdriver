# OpenCensus Stackdriver Exporter for PHP

This library provides an [`ExporterInterface`][exporter-interface] for exporting
Trace data to the [Stackdriver Trace][stackdriver-trace] service.

[![CircleCI](https://circleci.com/gh/census-ecosystem/opencensus-php-exporter-stackdriver.svg?style=svg)][ci-build]
[![Packagist](https://img.shields.io/packagist/v/opencensus/opencensus-exporter-stackdriver.svg)][packagist-package]
![PHP-Version](https://img.shields.io/packagist/php-v/opencensus/opencensus-exporter-stackdriver.svg)

## Installation & basic usage

1. Install the `opencensus/opencensus-exporter-stackdriver` package using [composer][composer]:

    ```bash
    $ composer require opencensus/opencensus-exporter-stackdriver:~0.1
    ```

1. Initialize a tracer for your application:

    ```php
    use OpenCensus\Trace\Tracer;
    use OpenCensus\Trace\Exporter\StackdriverExporter;

    Tracer::start(new StackdriverExporter());
    ```

## Customization

You may provide an associative array of options to the `StackdriverExporter` at
initialization:

```php
$options = [];
$exporter = new StackdriverExporter($options);
```

The following options are available:

| Option | Default | Description |
| ------ | ------- | ----------- |
| `client` | `new TraceClient($clientConfig)` | A configured [`TraceClient`][trace-client] to use to export traces |
| `clientConfig` | `[]` | Options to pass to the default TraceClient |


## Versioning

[![Packagist](https://img.shields.io/packagist/v/opencensus/opencensus-exporter-stackdriver.svg)][packagist-package]

This library follows [Semantic Versioning][semver].

Please note it is currently under active development. Any release versioned
0.x.y is subject to backwards incompatible changes at any time.

**GA**: Libraries defined at a GA quality level are stable, and will not
introduce backwards-incompatible changes in any minor or patch releases. We will
address issues and requests with the highest priority.

**Beta**: Libraries defined at a Beta quality level are expected to be mostly
stable and we're working towards their release candidate. We will address issues
and requests with a higher priority.

**Alpha**: Libraries defined at an Alpha quality level are still a
work-in-progress and are more likely to get backwards-incompatible updates.

**Current Status:** Alpha


## Contributing

Contributions to this library are always welcome and highly encouraged.

See [CONTRIBUTING](CONTRIBUTING.md) for more information on how to get started.

## Releasing

See [RELEASING](RELEASING.md) for more information on releasing new versions.

## License

Apache 2.0 - See [LICENSE](LICENSE) for more information.

## Disclaimer

This is not an official Google product.

[exporter-interface]: https://github.com/census-instrumentation/opencensus-php/blob/master/src/Trace/Exporter/ExporterInterface.php
[stackdriver-trace]: https://cloud.google.com/trace/
[census-org]: https://github.com/census-instrumentation
[composer]: https://getcomposer.org/
[semver]: http://semver.org/
[trace-client]: https://googleapis.github.io/google-cloud-php/#/docs/google-cloud/latest/trace/traceclient
[google-cloud-php]: https://github.com/googleapis/google-cloud-php
[packagist-package]: https://packagist.org/packages/opencensus/opencensus-exporter-stackdriver
[ci-build]: https://circleci.com/gh/census-ecosystem/opencensus-php-exporter-stackdriver
