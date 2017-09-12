create extension if not exists "uuid-ossp";

DROP TABLE IF EXISTS app_public.fhr_scheme_type CASCADE;

create table app_public.fhr_scheme_type (
  uuid             uuid primary key,
  scheme_type_id   integer NOT NULL,
  scheme_type_name text not null check (char_length(scheme_type_name) < 80),
  scheme_type_key  text check (char_length(scheme_type_key) < 80),
  created_at       timestamp default now(),
  updated_at       timestamp default now(),
  CONSTRAINT       unique_fhr_scheme_type_scheme_type_id UNIQUE(scheme_type_id),
  CONSTRAINT       unique_fhr_scheme_type_scheme_type_key UNIQUE(scheme_type_key)
);

comment on table app_public.fhr_scheme_type is
'Food Hygiene Rating Scheme Types.';
comment on column app_public.fhr_scheme_type.uuid is
'The primary unique identifier for the food hygiene rating scheme.';
comment on column app_public.fhr_scheme_type.scheme_type_id is
'The unique identifier for the food hygiene rating scheme as provided by http://api.ratings.food.gov.uk.';
comment on column app_public.fhr_scheme_type.scheme_type_name is
'The scheme type name.';
comment on column app_public.fhr_scheme_type.scheme_type_key is
'The scheme type key.';
comment on column app_public.fhr_scheme_type.created_at is
'The time this scheme was created.';
comment on column app_public.fhr_scheme_type.updated_at is
'The time this scheme was updated.';

