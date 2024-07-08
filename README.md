# Exclusive Reference

This module provides a setting for entity reference fields to declare
themselves as "exclusive reference" i.e. the target must be unique across
all instances of that field.

## Usage

Turn this feature "on" for an entity reference field in the field configuration.

When on, an entity will not save if that field would be writing non-unique
values. If the field is re-used across different bundles, it will check
across all of them. However, as fields cannot be used across entity types,
this validator cannot be used across entity types.



