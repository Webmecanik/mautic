# YellowboxCRM bundle

## Instalation

Require:

1. This PR https://github.com/Webmecanik/Automation_dev/pull/961
Warning: As I notice in that PR, this PR should not be required after this PR merged https://github.com/mautic/mautic/pull/7754 (not testet yet)

2. New plugin integration https://github.com/mautic-inc/plugin-integrations

3. This bundle install to plugins/MauticYellowboxCrmBundle

Don't forget remove cache

## Setup

Setup is very easy. 

1. Just set URL and username/password access to Yellowbox.
2. If authentification failed, form return error. 
3. If success, you should see matching fields and settings
4. Setup is very intuitive

Small legends:

Choose owner for contacts created on Yellowbox - contact created on Yellowbox has setuped this owner
Set owner also for update contacts on Yellowbox - owner is overwrite

<img src="https://user-images.githubusercontent.com/462477/64603736-225a4180-d3c1-11e9-8dbb-5de5a43317f7.png" width="400">

Do Not Contact is handle at the moment in based way - that means any fields from integration can send 0 or 1 to Mautic. Matching allow select these column in list

<img src="https://user-images.githubusercontent.com/462477/64595444-3ea2b200-d3b2-11e9-903d-e60e8b8ce85b.png" width="400">

### Sync

By command

php app/console mautic:integrations:sync YellowboxCrm

Options for sync command: https://github.com/mautic-inc/plugin-integrations#sync-command

## Requirements

 * mautic/mautic: ^2.14,
 * mautic/mautic-integrations-bundle: master,
 * php: ^7.1 || ^7.2,
 * ext-mbstring
 * symfony/cache
 * ext-json

## Features supported
### Contact owner
When a contact/lead/company is fetched from CRM, the owner is assigned to the contact/company in Mautic based on Mautic users. 3 options in the sync. scenario:
1. Owner exists as user un Mautic, entity is assigned.
2. Owner is not existing in Mautic, user is created with the chosen default role (user rights).
3. Owner in CRM doesn't have email address, the owner is not synced.
