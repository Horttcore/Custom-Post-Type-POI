# Custom Post Type Point of Interests

A custom post type to manage point of interests

## Supports

* Title
* Editor
* Excerpt
* Thumbnail

## Custom Fields

* Street
* Street number
* ZIP
* City
* Address additional
* Region
* Country
* Latitude / Longitdude ( Google Maps GeoAPI )

## Language Support

* english
* german

## Hooks

### Actions

* `poi-location-table-before` - Before the location table
* `poi-location-table-first` - Before the first row of the location table
* `poi-location-table-last` - After the last row of the location table
* `poi-location-table-after` - After the location table
* `save-poi-meta` - Runs when the point of interes location is saved; Location is passed as argument

### Filter

* `poi-location` - Get the location data
* `poi-location-save` - The location data that is saved into post_meta
* `poi-get-lat-lng` - Get latitude longitude

## Changelog

### v0.3

* Added hook: `poi-get-lat-lng`
* Enhancement: Cleanup

### v0.2

* Enhancement: Manual location check
* Bugfix: GeoAPI was not saved.

### v0.1

* Initial release
