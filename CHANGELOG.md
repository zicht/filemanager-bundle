## Version 4.6.0
### fixes
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
