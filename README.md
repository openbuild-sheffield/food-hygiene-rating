# UK Food Hygiene Rating Data

Data in Postgres SQL from http://api.ratings.food.gov.uk 

You can run this image directly or you can just extract the latest data.

## List latest files

`docker run --rm --entrypoint ls openbuild-sheffield/food-hygiene-rating:latest /export/`

## Pipe example

`docker run --rm --entrypoint cat openbuild-sheffield/food-hygiene-rating:latest /export/001.SchemeTypes.sql > /tmp/001.SchemeTypes.sql`
