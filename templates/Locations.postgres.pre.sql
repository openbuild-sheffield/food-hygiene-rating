create extension if not exists "uuid-ossp";

DROP TABLE IF EXISTS app_public.fhr_country CASCADE;
DROP TABLE IF EXISTS app_public.fhr_region CASCADE;

create table app_public.fhr_country (
  uuid       uuid primary key,
  id         integer NOT NULL,
  name       text not null check (char_length(name) < 80),
  name_key   text check (char_length(name_key) < 80),
  created_at timestamp default now(),
  updated_at timestamp default now(),
  CONSTRAINT unique_fhr_country_id UNIQUE(id)
);

create table app_public.fhr_region (
  uuid       uuid primary key,
  id         integer NOT NULL,
  name       text not null check (char_length(name) < 80),
  name_key   text check (char_length(name_key) < 80),
  code       text check (char_length(code) < 80),
  country_id integer NOT NULL references app_public.fhr_country(id),
  created_at timestamp default now(),
  updated_at timestamp default now(),
  CONSTRAINT unique_fhr_region_id UNIQUE(id),
  CONSTRAINT unique_fhr_region_code UNIQUE(code)
);

comment on table app_public.fhr_country is
'Food Hygiene Rating Scheme Countries.';
comment on column app_public.fhr_country.uuid is
'The primary unique identifier for the food hygiene rating country.';
comment on column app_public.fhr_country.id is
'The unique identifier for the food hygiene rating country as provided by http://api.ratings.food.gov.uk.';
comment on column app_public.fhr_country.name is
'The country name.';
comment on column app_public.fhr_country.name_key is
'The country key.';
comment on column app_public.fhr_country.created_at is
'The time this scheme was created.';
comment on column app_public.fhr_country.updated_at is
'The time this scheme was updated.';

comment on table app_public.fhr_region is
'Food Hygiene Rating Scheme Regions.';
comment on column app_public.fhr_region.uuid is
'The primary unique identifier for the food hygiene rating region.';
comment on column app_public.fhr_region.id is
'The unique identifier for the food hygiene rating region as provided by http://api.ratings.food.gov.uk.';
comment on column app_public.fhr_region.name is
'The region name.';
comment on column app_public.fhr_region.name_key is
'The region name key.';
comment on column app_public.fhr_region.code is
'The region code.';
comment on column app_public.fhr_region.created_at is
'The time this scheme was created.';
comment on column app_public.fhr_region.updated_at is
'The time this scheme was updated.';

