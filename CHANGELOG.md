## 3.0.0
### NOTE
This will be the last major release for this library: it is deprecated and you should move on from it.

### Changes
* Set fallback time for modification date (#37)

### BC breaks
* Dropped support for PHP 5.5 and 5.6: only PHP 7 is supported now
* Dropped support for Symfony versions older than 2.8

## 2.0.0
### Changes
* Upgraded Guzzle to v6, now using PSR-7
* Added `ArrayItemLogger`, mostly useful for testing
* Default time to run went from 300 to 1200 seconds for import.part jobs and is now configurable
* Added `JsonLinesReaderType`

### BC breaks
* While not a direct BC-break, Guzzle has been upgraded, so your project also
  needs to.
* Removed `<service>.class` parameters: services now directly state their class
* Moved tests outside of the project's source. Helper classes for tests are kept
  but have moved to a different namespace. If you extended classes like `AbstractFeedTypeTest`
  or `DefaultFeedTypeTest`, you need to fix the namespaces. Also these classes
  have been renamed to `*TestCase`.
* Imports are finished right away if there are no parts, therefor the `io:import:cleanup` 
  command is no longer needed and should be removed as cronjob. 


## 1.0.4
### Changes
* Added missing import-start event


## 1.0.3
### Changes
* Improved bulleted list handling in Markdown transformer


## 1.0.2
### Changes
* Added syndication field to feed


## 1.0.1
### Changes
* Increased default ttr for imports


## 1.0.0
First stable release
