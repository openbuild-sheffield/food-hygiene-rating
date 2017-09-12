create extension if not exists "uuid-ossp";

DROP TABLE IF EXISTS app_public.fhr_business_type CASCADE;

create table app_public.fhr_business_type (
  uuid             uuid primary key,
  business_type_id   integer NOT NULL,
  business_type_name text not null check (char_length(business_type_name) < 80),
  created_at       timestamp default now(),
  updated_at       timestamp default now(),
  CONSTRAINT       unique_fhr_business_type_business_type_id UNIQUE(business_type_id)
);

comment on table app_public.fhr_business_type is
'Food Hygiene Rating Business Types.';
comment on column app_public.fhr_business_type.uuid is
'The primary unique identifier for the food hygiene rating business type.';
comment on column app_public.fhr_business_type.business_type_id is
'The unique identifier for the food hygiene rating business as provided by http://api.ratings.food.gov.uk.';
comment on column app_public.fhr_business_type.business_type_name is
'The business type name.';
comment on column app_public.fhr_business_type.created_at is
'The time this business type was created.';
comment on column app_public.fhr_business_type.updated_at is
'The time this business type was updated.';

