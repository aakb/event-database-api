Feature: Occurrences
  In order to manage occurrences
  As a client software developer
  I need to be able to retrieve, create, update and delete occurrences trough the API.

  Background:
    Given the following users exist:
      | username   | password | roles          |
      | api-read   | apipass  | ROLE_API_READ  |
      | api-write  | apipass  | ROLE_API_WRITE |
      | api-write2 | apipass  | ROLE_API_WRITE |

  @createSchema
  Scenario: Anonymous access
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  Scenario: Count Occurrences
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member" should have 0 elements

  Scenario: Create an event with multiple occurrences
    When I authenticate as "api-write"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/events" with body:
    """
    {
      "name": "Repeating event",
      "occurrences": [ {
        "startDate": "2007-01-01",
        "endDate": "2009-01-01",
        "place": {
          "name": "Some place"
        }
      },
      {
        "startDate": "2023-11-01",
        "endDate": "2027-05-01",
        "place": {
          "name": "Another place"
        }
      } ]
    }
    """
    Then the response status code should be 201

  Scenario: Count Occurrences
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&endDate[after]=@0"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member" should have 2 elements
    And the JSON node "hydra:member[0].event.@id" should be equal to "/api/events/1"

  Scenario: Order by startDate
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&endDate[after]=@0&order[startDate]=asc"
    And the JSON node "hydra:member" should have 2 elements
    And the JSON node "hydra:member[0].@id" should be equal to "/api/occurrences/1"
    And the JSON node "hydra:member[1].@id" should be equal to "/api/occurrences/2"

    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&endDate[after]=@0&order[startDate]=desc"
    And print last JSON response
    And the JSON node "hydra:member" should have 2 elements
    And the JSON node "hydra:member[0].@id" should be equal to "/api/occurrences/2"
    And the JSON node "hydra:member[1].@id" should be equal to "/api/occurrences/1"

  Scenario: Filter by date range
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=2000-01-01&endDate[before]=2010-01-10"
    And the JSON node "hydra:member" should have 1 element
    And the JSON node "hydra:member[0].@id" should be equal to "/api/occurrences/1"
    And the JSON node "hydra:member[0].event.@id" should be equal to "/api/events/1"

    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=2020-01-01&endDate[before]=2100-01-01"
    And the JSON node "hydra:member" should have 1 element
    And the JSON node "hydra:member[0].@id" should be equal to "/api/occurrences/2"
    And the JSON node "hydra:member[0].event.@id" should be equal to "/api/events/1"

  Scenario: Filter by place name
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&endDate[after]=@0&place.name=Some place"
    And the JSON node "hydra:member" should have 1 element
    And the JSON node "hydra:member[0].@id" should be equal to "/api/occurrences/1"
    And the JSON node "hydra:member[0].event.@id" should be equal to "/api/events/1"

    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&place.name=Another place"
    And the JSON node "hydra:member" should have 1 element
    And the JSON node "hydra:member[0].@id" should be equal to "/api/occurrences/2"
    And the JSON node "hydra:member[0].event.@id" should be equal to "/api/events/1"

  Scenario: Filter by event name
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&endDate[after]=@0&event.name=Repeating+event"
    And the JSON node "hydra:member" should have 2 elements
    And the JSON node "hydra:member[0].@id" should be equal to "/api/occurrences/1"
    And the JSON node "hydra:member[0].event.@id" should be equal to "/api/events/1"

    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&endDate[after]=@0&event.name=eat"
    And the JSON node "hydra:member" should have 2 elements
    And the JSON node "hydra:member[0].@id" should be equal to "/api/occurrences/1"
    And the JSON node "hydra:member[0].event.@id" should be equal to "/api/events/1"

    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&event.name=Another event"
    And the JSON node "hydra:member" should have 0 elements

  Scenario: Filter by created by
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&endDate[after]=@0&event.createdBy=2"
    And the JSON node "hydra:member" should have 2 elements

    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&endDate[after]=@0&event.createdBy[]=2&event.createdBy[]=87"
    And the JSON node "hydra:member" should have 2 elements

    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0&endDate[after]=@0&event.createdBy=87"
    And the JSON node "hydra:member" should have 0 elements

  Scenario: Delete event
    When I authenticate as "api-write"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "DELETE" request to "/api/events/1"
    Then the response status code should be 204

  Scenario: Count Occurrences
    When I authenticate as "api-write"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/occurrences?startDate[after]=@0"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member" should have 0 elements

  Scenario: Create an event with multiple occurrences
    When I authenticate as "api-write"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/events" with body:
    """
    {
      "name": "Repeating event",
      "occurrences": [ {
        "startDate": "2000-01-01",
        "endDate": "2001-01-01",
        "place": {
          "name": "Some place"
        }
      },
      {
        "startDate": "2020-01-01",
        "endDate": "2025-01-01",
        "place": {
          "name": "Another place"
        }
      } ]
    }
    """
    Then the response status code should be 201
    And the JSON node "occurrences" should have 2 elements
    And the JSON node "occurrences[0].@id" should be equal to "/api/occurrences/3"
    And the JSON node "occurrences[1].@id" should be equal to "/api/occurrences/4"

  Scenario: Update an event with multiple occurrences
    When I authenticate as "api-write"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "PUT" request to "/api/events/2" with body:
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
               "startDate": "2000-01-01T00:00:00+01:00",
               "endDate": "2001-01-01T00:00:00+01:00",
               "place": {
                   "@id": "\/api\/places\/1",
                   "@type": "http:\/\/schema.org\/Place",
                   "logo": null,
                   "description": null,
                   "image": null,
                   "name": "Some place",
                   "url": null,
                   "videoUrl": null,
                   "langcode": null
               },
               "ticketPriceRange": null,
               "eventStatusText": null
           },
           {
               "@id": "\/api\/occurrences\/4",
               "@type": "Occurrence",
               "event": "\/api\/events\/2",
               "startDate": "2020-01-01T00:00:00+01:00",
               "endDate": "2027-01-01T00:00:00+01:00",
               "place": {
                   "@id": "\/api\/places\/2",
                   "@type": "http:\/\/schema.org\/Place",
                   "logo": null,
                   "description": null,
                   "image": null,
                   "name": "Another place",
                   "url": null,
                   "videoUrl": null,
                   "langcode": null
               },
               "ticketPriceRange": null,
               "eventStatusText": null
           }
       ],
       "ticketPurchaseUrl": null,
       "excerpt": null,
       "tags": [],
       "description": null,
       "image": null,
       "name": "Repeating event",
       "url": null,
       "videoUrl": null,
       "langcode": null
     }
     """

    Then the response status code should be 200
    And the JSON node "occurrences" should have 2 elements
    And the JSON node "occurrences[0].@id" should be equal to "/api/occurrences/3"
    And the JSON node "occurrences[1].@id" should be equal to "/api/occurrences/4"

  Scenario: Get an event with multiple occurrences
    When I authenticate as "api-write"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/events/2"

    Then the response status code should be 200
    And the JSON node "occurrences" should have 2 elements
    And the JSON node "occurrences[0].@id" should be equal to "/api/occurrences/3"
    And the JSON node "occurrences[1].@id" should be equal to "/api/occurrences/4"

  Scenario: Update an event with a single occurrences
    When I authenticate as "api-write"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "PUT" request to "/api/events/2" with body:
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
               "startDate": "2000-01-01T00:00:00+01:00",
               "endDate": "2001-01-01T00:00:00+01:00",
               "place": {
                   "@id": "\/api\/places\/1",
                   "@type": "http:\/\/schema.org\/Place",
                   "logo": null,
                   "description": null,
                   "image": null,
                   "name": "Some place",
                   "url": null,
                   "videoUrl": null,
                   "langcode": null
               },
               "ticketPriceRange": null,
               "eventStatusText": null
           }
       ],
       "ticketPurchaseUrl": null,
       "excerpt": null,
       "tags": [],
       "description": null,
       "image": null,
       "name": "Repeating event",
       "url": null,
       "videoUrl": null,
       "langcode": null
     }
     """

    Then the response status code should be 200
    And the JSON node "occurrences" should have 1 element
    And the JSON node "occurrences[0].@id" should be equal to "/api/occurrences/3"

  Scenario: Get an event with a single occurrences
    When I authenticate as "api-write"
    And I add "Content-Type" header equal to "application/ld+json"
    And I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/api/events/2"

    Then the response status code should be 200
    And the JSON node "occurrences" should have 1 element
    And the JSON node "occurrences[0].@id" should be equal to "/api/occurrences/3"

  @dropSchema
  Scenario: Drop schema
