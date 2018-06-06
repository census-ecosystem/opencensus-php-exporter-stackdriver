# Releasing OpenCensus PHP

The PHP library and extension are released independently of each other.

## Packagist Package

1. Bump the `VERSION` constant in [`StackdriverExporter`][exporter]

1. Create a GitHub release.

1. Click `Update` from the admin view of the [opencensus/opencensus-exporter-stackdriver][packagist] package.

[packagist]: https://packagist.org/packages/opencensus/opencensus-exporter-stackdriver
[exporter]: https://github.com/census-ecosystem/opencensus-php-exporter-stackdriver/blob/master/src/StackdriverExporter.php
