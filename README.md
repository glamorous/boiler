Boiler
========

[![Latest Stable Version](https://poser.pugx.org/glamorous/boiler/v/stable)](https://packagist.org/packages/glamorous/boiler)
[![License](https://img.shields.io/github/license/glamorous/boiler.svg)](https://github.com/glamorous/boiler)
[![PHP Version](https://img.shields.io/packagist/php-v/glamorous/boiler.svg)]()
[![Build Status](https://img.shields.io/travis/glamorous/boiler.svg)](https://travis-ci.org/glamorous/boiler)
[![Codecov](https://img.shields.io/codecov/c/github/glamorous/boiler.svg)](https://codecov.io/gh/glamorous/boiler)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/glamorous/boiler/badges/quality-score.png)](https://scrutinizer-ci.com/g/glamorous/boiler/)
[![Total Downloads](https://img.shields.io/packagist/dt/glamorous/boiler.svg)](https://packagist.org/packages/glamorous/boiler)
[![GitHub issues](https://img.shields.io/github/issues/glamorous/boiler.svg)](https://github.com/glamorous/boiler/issues)

Boiler is a framework to help you create (web)projects instead of using a skeleton project.
The reason why this project is built is to win time by not updating your dependencies in your skeleton project.


Installation
-------------

``
composer global require glamorous/boiler
``

Make sure the `~/.composer/vendor/bin` directory is in your system's "PATH".


How to use it?
--------------

### Create a folder for your (custom) boiler templates

You can add multiple directories to allow boiler to search templates. To add a directory you only need to run:

```
boiler setup my/path
```

### Create a boiler templates

A boiler template is a simple yaml file with a couple of steps to execute, so the result is a project.

```
name: Default project

steps:
  - create_readme
  - git

create_readme:
  name: Readme
  script:
    - touch README.md
    - echo '{#PROJECT_NAME#}
           ==============
           
           This is the default README.md created by boiler.' >README.md

git:
    name: Initialized Git and create first commit
    script:
        - git init
        - git commit -m 'Initial commit'

```

In the above example:
- added a README.md file
- initialize git & created first commit

### Create a project with your created template

Creating a project based on your `default.yml` template is as simple as:

```
boiler create default
```

The boiler script will create a directory `default` and run the scripts from your template to create your project.

#### Extra options

`--dir`

Define the name of the directory yourself

`--name`

Define the name of the project (can be used in your template as `{#PROJECT_NAME#}`)


Include templates
------------------

It's possible to include other created templates with extra functions to re-use.
Instead of duplicating specific functions for every type of project, you can just include it and call it;

`functions.yml` in a directory where `boiler setup` was run:

```
create_readme:
  name: Readme
  script:
    - touch README.md
    - echo '{#PROJECT_NAME#}
           ==============
           
           This is the default README.md created by boiler.' >README.md

git:
    name: Initialized Git and create first commit
    script:
        - git init
        - git commit -m 'Initial commit'

```

`default.yml` in a directory where `boiler setup` was run:

```
name: Default project

include:
  - functions

steps:
  - create_readme
  - git

```

Commands
---------

`boiler create my-template`: Create an application based on the "my-template".

`boiler setup my/path`: Set up a directory as a template directory (path is optional, current directory will be taken).

`boiler remove my/path`: Remove a directory from the template directories (path is optional, current directory will be taken).

`boiler paths`: Show all included template directories.


Changelog
---------

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

Testing
--------

``` bash
$ composer test
```

Contributing
-------------

Please see [CONTRIBUTING](CONTRIBUTING.md) for details. To see a list of the contributors: [all contributors](../../contributors).

License
---------

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
