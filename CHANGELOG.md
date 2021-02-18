
# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added|Changed|Deprecated|Removed|Fixed|Security
Nothing so far

## 7.0.3 - 2021-02-18
### Changed
- Forward merge of v6.0.3: Improve Twig templates compatibility

## 7.0.2 - 2020-05-15
### Changed
- Switched from PSR-0 to PSR-4 autoloading (merged in from v6.0.2)

## 7.0.1 - 2019-11-07
### Fixed
- Bugfix for the metadata driver

## 7.0.0 - 2019-11-06
### Changed
- Minimum PHP version from 7.0 to 7.2
- Switched jms/metadata from v1 to v2
### Removed
- Author file tag/annotation (inherited from unrelease v6 changes)
### Fixed
- Drop false positives in deprecations check of OptionsResolverInterface (inherited from unrelease v6 changes)

## 6.0.3 - 2021-02-18
### Changed
- Improve Twig templates compatibility

## 6.0.2 - 2020-05-15
### Changed
- Switched from PSR-0 to PSR-4 autoloading
### Removed
- Author file tag/annotation
### Fixed
- Drop false positives in deprecations check of OptionsResolverInterface

## 6.0.1 - 2019-09-14
### Added
- English translations (merged in from v5.0.2)

## 6.0.0 - 2018-06-26
### Added
- Support for Symfony 3.x
### Removed
- Support for Symfony 2.x

## 5.0.0
### Changed
From this version on the minimal PHP requirement is `7.0`

## Version 4.6.0
### Fixed
clearing the cache for imagine for files outside the web root is disabled.

## Version 4.3.0
### Changes
Default behaviour of the bundle is to systemize *and* lower case file names. By including the following, the
FileManagerBundle will not transform the original case.

zicht_file_manager.yml

```
zicht_file_manager:
    case_preservation: true
```

Use case: Some clients are specific in naming their files, such as technical documents.
Eg H2-drystar-X1234Ya.pdf v.s. h2-drystar-x1234ya.pdf

## Version 3.0.2
### Changes
- bugfix for showing the image preview with the edit field, when using multiple zicht_file fields in the same form

## Version 3.0.1
### Changes
- some bugfixes

## Version 3.0.0
### Breaking Changes
- added support for Symfony >= _2.3_
- needed to switch some logic, since FormBuilder doesn't have getParent() anymore

## Version 2.4.*
- Symfony < _2.3_
