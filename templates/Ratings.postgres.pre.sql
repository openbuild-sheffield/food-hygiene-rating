create extension if not exists "uuid-ossp";

DROP TABLE IF EXISTS app_public.fhr_rating CASCADE;

create table app_public.fhr_rating (
  uuid            uuid primary key,
  rating_id       integer NOT NULL,
  rating_name     text not null check (char_length(rating_name) < 80),
  rating_key      text check (char_length(rating_key) < 80),
  rating_key_name text check (char_length(rating_key_name) < 80),
  scheme_type_id  integer NOT NULL references app_public.fhr_scheme_type(scheme_type_id),
  created_at      timestamp default now(),
  updated_at      timestamp default now(),
  CONSTRAINT      unique_fhr_rating_rating_id UNIQUE(rating_id),
  CONSTRAINT      unique_fhr_rating_rating_key UNIQUE(rating_key)
);

comment on table app_public.fhr_rating is
'Food Hygiene Rating Ratings.';
comment on column app_public.fhr_rating.uuid is
'The primary unique identifier for the food hygiene rating.';
comment on column app_public.fhr_rating.rating_id is
'The unique identifier for the food hygiene rating scheme as provided by http://api.ratings.food.gov.uk.';
comment on column app_public.fhr_rating.rating_name is
'The rating name.';
comment on column app_public.fhr_rating.rating_key is
'The rating key.';
comment on column app_public.fhr_rating.rating_key_name is
'The rating key name.';
comment on column app_public.fhr_rating.scheme_type_id is
'The link to fhr_scheme_type.scheme_type_id';
comment on column app_public.fhr_rating.created_at is
'The time this scheme was created.';
comment on column app_public.fhr_rating.updated_at is
'The time this scheme was updated.';

