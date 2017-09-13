# UK Food Hygiene Rating Data

Data in Postgres SQL from http://api.ratings.food.gov.uk

You can run this image directly or you can just extract the latest data.

## Tags

Use the following if you just want the SQL files

`openbuild/food-hygiene-rating:no-cache`

Use the following if you want to run the service locally, this caches the individual establishment files and will only download new ones if the rating date is different from the list establishments file.  Warning this file is large.

`openbuild/food-hygiene-rating:latest`

## List latest files

`docker run --rm --entrypoint ls openbuild/food-hygiene-rating:no-cache /export/`

## Pipe example

`docker run --rm --entrypoint cat openbuild/food-hygiene-rating:no-cache /export/001.SchemeTypes.sql > /tmp/001.SchemeTypes.sql`
