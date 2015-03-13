## Origin and Sources

The IoBundle works with origins and sources:

### Origin

An external place where data is imported from. This can be a third party who
supplies a feed, a web service with an API, or a site that you scrape - with
permission of course ;)

### Source

An item that was imported from an origin. The way that the item was imported
is irrelevant; it could be a feed, API, form input, anything. The point is that
every origin supplies data, which is imported as sources.

### Entities
A source can represent any entity in your project, which is why you need to
provide the model mapping for it yourself. The IoBundle provides an
[interface](/src/TreeHouse/IoBundle/Model/SourceInterface.php) that the entity needs
to implement. Additionally you can provide the associations to the entity that
the source represents.

Likewise the Origin model needs to be implemented using the
[OriginInterface](/src/TreeHouse/IoBundle/Model/OriginInterface.php). This entity
does not always need additional mapping like the Source entity, but it is
possible if you want to.

### Linking and deduplicating sources

Since multiple origins can provide sources, there can be multiple source
records that represent the same entity. Let's say we're importing car data from
two origins: an aggregator named AwesomeCars and a dealer named AcmeCars. Both
supply a feed which we're importing
(see [chapter 3: importing feeds](03-importing.md)).
This results in two sources:

```yaml
# pseudo data, linked entities are prefixed with @
source1:
  @origin: awesome-cars
  original_id: 1234ab
  data:
    vin: 2334647SDGRTHB457547FGTJRTY
    make: Ford
    model: Focus
    doors: 5

source2:
  @origin: acme-cars
  original_id: 76556
  data:
    vin: 2334647SDGRTHB457547FGTJRTY
    make: Ford
    model: Focus
    type: hatchback
```

In our example, the linked entity for a source is a car. That means that from
one or more Source entities, a Car entity is provisioned. The unique key for a
car is the VIN (vehicle identification number), which both sources provide.
Based on the VIN we can now link both sources to the same Car entity, see
[chapter 4: source processing](04-processing.md) for more details.
