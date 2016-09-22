Feature: Events
  In order to manage events
  As a client software developer
  I need to be able to retrieve, create, update and delete events trough the API.

  @createSchema
  Scenario: Create an event with multiple occurrences and a single place
     When I authenticate as "api-write"
     When I send a "POST" request to "/api/events" with body:
     """
     {
       "name": "Repeating event with multiple places",
       "occurrences": [ {
         "startDate": "2000-01T00:00:00+00:00",
         "endDate": "2100-01T00:00:00+00:00",
         "place": {
           "name": "Place 1"
         }
       },
       {
         "startDate": "2000-01T00:00:00+00:00",
         "endDate": "2100-01T00:00:00+00:00",
         "place": {
           "name": "Place 1"
         }
       } ]
     }
     """
     Then the response status code should be 201
     And the response should be in JSON
     And the header "Content-Type" should be equal to "application/ld+json"
     And the JSON should be valid according to the schema "features/schema/api.event.response.schema.json"
     And the JSON should not differ from:
     """
     {
       "@context": "\/api\/contexts\/Event",
       "@id": "\/api\/events\/1",
       "@type": "http:\/\/schema.org\/Event",
       "occurrences": [
         {
           "@id": "\/api\/occurrences\/1",
           "@type": "Occurrence",
           "event": "\/api\/events\/1",
           "startDate": "2000-01-01T00:00:00+00:00",
           "endDate": "2100-01-01T00:00:00+00:00",
           "place": {
             "@id": "\/api\/places\/1",
             "@type": "http:\/\/schema.org\/Place",
             "logo": null,
             "description": null,
             "image": null,
             "name": "Place 1",
             "url": null,
             "videoUrl": null,
             "langcode": null
           },
           "ticketPriceRange": null,
           "eventStatusText": null
         },
         {
           "@id": "\/api\/occurrences\/2",
           "@type": "Occurrence",
           "event": "\/api\/events\/1",
           "startDate": "2000-01-01T00:00:00+00:00",
           "endDate": "2100-01-01T00:00:00+00:00",
           "place": {
             "@id": "\/api\/places\/1",
             "@type": "http:\/\/schema.org\/Place",
             "logo": null,
             "description": null,
             "image": null,
             "name": "Place 1",
             "url": null,
             "videoUrl": null,
             "langcode": null
           },
           "ticketPriceRange": null,
           "eventStatusText": null
         }
       ],
       "ticketPurchaseUrl": null,
       "tags": [],
       "description": null,
       "image": null,
       "name": "Repeating event with multiple places",
       "url": null,
       "videoUrl": null,
       "langcode": null
     }
     """

  Scenario: Create an event with a single occurrence and a single place by reference
     When I authenticate as "api-write"
     When I send a "POST" request to "/api/events" with body:
     """
     {
       "name": "Repeating event with multiple places",
       "occurrences": [ {
         "startDate": "2000-01T00:00:00+00:00",
         "endDate": "2100-01T00:00:00+00:00",
         "place": {
           "@id": "/api/places/1"
         }
       } ]
     }
     """
     Then the response status code should be 201
     And the response should be in JSON
     And the header "Content-Type" should be equal to "application/ld+json"
     And the JSON should be valid according to the schema "features/schema/api.event.response.schema.json"
     And the JSON should not differ from:
     """
     {
       "@context": "\/api\/contexts\/Event",
       "@id": "\/api\/events\/2",
       "@type": "http:\/\/schema.org\/Event",
       "occurrences": [
         {
           "@id": "\/api\/occurrences\/3",
           "@type": "Occurrence",
           "event": "\/api\/events\/2",
           "startDate": "2000-01-01T00:00:00+00:00",
           "endDate": "2100-01-01T00:00:00+00:00",
           "place": {
             "@id": "\/api\/places\/1",
             "@type": "http:\/\/schema.org\/Place",
             "logo": null,
             "description": null,
             "image": null,
             "name": "Place 1",
             "url": null,
             "videoUrl": null,
             "langcode": null
           },
           "ticketPriceRange": null,
           "eventStatusText": null
         }
       ],
       "ticketPurchaseUrl": null,
       "tags": [],
       "description": null,
       "image": null,
       "name": "Repeating event with multiple places",
       "url": null,
       "videoUrl": null,
       "langcode": null
     }
     """

  Scenario: Cannot create an event with a single occurrence and a single place by invalid reference
     When I authenticate as "api-write"
     When I send a "POST" request to "/api/events" with body:
     """
     {
       "name": "Repeating event with multiple places",
       "occurrences": [ {
         "startDate": "2000-01T00:00:00+00:00",
         "endDate": "2100-01T00:00:00+00:00",
         "place": {
           "@id": "/api/places/2"
         }
       } ]
     }
     """
     Then the response status code should be 400
     And the response should be in JSON
     And the JSON node "hydra:description" should be equal to 'Item not found for "/api/places/2".'

  @dropSchema
  Scenario: Drop schema
