
## Reply functionality: single-click validation

### Use Case

"Reply" functionality allows users to submit survey responses or update their preferences without first completing the primary hub authentication procedure.

This feature provides user authentication via hashes of randomly-generated codes that are associated with the user's CMS record, the campaign, and the Hub.

This functionality is now part of the `com_newsletter` core component. It was originally found in custom component `com_reply`.

Related features:

All management of these features is found under the Hub's administrator interface.

* Per-user randomly generated codes are managed under Member password management.
* Per-campaign randomly generated codes are managed under the Newsletter component's Campaign tab, on the administrative interface.
* The Hub's randomly generated code is managed under Global Configuration on the administrative interface.

### Install
1. Run the migrations e.g. via `muse migration run ...`

### Adding a Page
It is assumed that all users providing correct credentials will have access to all pages.

To add a form-based page:
1. Add a record to `jos_reply_pages`
1. Create a view for the page in `site/views/pages` 

### Adding an Email Subscription Option
1. Create or confirm name of the corresponding profile field
1. Add a record to `jos_email_subscriptions`
1. Create a view (content to appear beneath label) if desired 
1. Set the email page ID in `secrets/code.php`, using `secrets/code_example.php` as a model.
    * The page ID key is referenced in CodeHelper.php `validateEmailSubscriptionsCode()`.

### Gotchas
* `jos_email_subscriptions.profile_field_name` must correspond with a `jos_user_profile_fields.name`
* `jos_user_profile_options` must be populated for the user profile fields. `jos_user_profile_options.field_id` must correspond with id from `jos_user_profile_fields.id`
* `jos_users` user codes are provided at login time by the core Hubzero members plugin.
* Hashed access codes are provided by the stored procedure, which can be accessed from the database.
    It takes two arguments, `campaign`.`id` and `jos_users`.`username`

### Component-Specific Tables
* `jos_reply_pages` - pages for collecting data
* `jos_reply_replies` - user-submitted responses
* `jos_email_subscriptions` - email subscription options

### Access-Related Tables
* `jos_users` - contains a `secret` for each user
* `campaign` - contains a `secret` for each campaign, with expiration date
* `jos_config` - the `value` column for `scope`='hub' and `key`='secret' contains the overall secret for the Hub

### Integrations
The email subscription update functionality integrates with the core user profile data.
* `jos_user_profile_fields` - profile fields
* `jos_user_profile_options` - profile field response options
* `jos_user_profiles` - associates user data with profile fields

### Generating Access Codes

The stored procedure `hash_access_code` is used to generate and verify codes for page access. Codes are used in URLs as shown below. 

### Example URLs

Here:

* USERNAME is the user's jos_user.username
* CAMPAIGNID is campaign.id


#### pages
`https://jsperhac.aws.hubzero.org/newsletter/pages/2?campaign=CAMPAIGNID&user=USERNAME&code=CODE_FROM_hash_access_code`

#### subscriptions
`https://jsperhac.aws.hubzero.org/newsletter/email-subscriptions?campaign=CAMPAIGNID&user=USERNAME&code=CODE_FROM_hash_access_code`