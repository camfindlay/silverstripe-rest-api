REST module for Silverstripe
============================

This module models the REST api in a simple fashion.

All routes for the different resources should resolved by a controller which extends the BaseRestController.

After that, you can add your routes into your YAML config file.

## Features

 * Queries
 * Field list
 * Different serialisation formats
 * Pagination
 * Presenters
 
## Usage

Every endpoint has its own controller. The controller should extend `BaseRestController`.


## Testing

For functional tests, you can extend the `RestTest` class and use it to test your application. We recommend to use 
fixtures for testing like it is explained in the Silverstripe documentation.
