# Content Access Limiter

A Drupal module that allows administrators to limit the number of content items users can access per day.

## Features

- Set a daily access limit for users
- Select which content types should be subject to access limits
- Configure roles that can bypass access limits
- View access statistics and reset user access counts
- User-friendly access limit reached page

## Installation

1. Place the module in your custom modules directory (`/web/modules/custom/content_access_limiter`)
2. Enable the module through the Drupal admin interface or using Drush:
   ```bash
   drush en content_access_limiter
   ```

## Module Pages and Paths

- **Settings Page**: `/admin/config/content-access-limiter`
  - Configure daily access limits
  - Select bypass roles
  - Choose content types to limit

- **Access Report**: `/admin/reports/content-access-limiter`
  - View user access statistics
  - Reset user access counts
  - Monitor daily usage

- **Access Limit Reached**: `/access-limit-reached`
  - Shown when users reach their daily limit
  - Displays remaining time until reset

## Configuration Settings

### Daily Access Limit
- **Path**: `/admin/config/content-access-limiter`
- **Description**: Set the maximum number of content items users can access per day
- **Default**: 10
- **Minimum**: 1
- **Location**: Under "Content Access Limits" fieldset

### Bypass Roles
- **Path**: `/admin/config/content-access-limiter`
- **Description**: Select user roles that can access content without limits
- **Options**: All available user roles
- **Default**: None selected
- **Location**: Under "Content Access Limits" fieldset

### Content Types
- **Path**: `/admin/config/content-access-limiter`
- **Description**: Choose which content types should be subject to access limits
- **Options**: All installed content types
- **Default**: None selected
- **Location**: Under "Content Types" fieldset

## Usage

### For Administrators

- **Access Statistics**: `/admin/reports/content-access-limiter`
  - View all users' access counts
  - See last access times
  - Reset individual user counts
  - Monitor role-based access

- **Settings Management**: `/admin/config/content-access-limiter`
  - Adjust daily limits
  - Modify bypass roles
  - Update limited content types

### For Users

- **Content Access**: Any content page
  - Access count increments for limited content types
  - Bypass roles access without counting
  - Daily limit resets at midnight

- **Limit Reached**: `/access-limit-reached`
  - Informs users of limit reached
  - Shows when access will reset
  - Provides clear error message

## Access Logs

The module maintains a log of content access with the following information:
- User ID
- Node ID
- Access timestamp

Logs are automatically cleared at the start of each day.

## Permissions

The module requires the following permissions:
- `administer site configuration`: Required to configure access limits
- `access content`: Required to view content (subject to access limits)

## Troubleshooting

If access limits are not working as expected:
1. Verify that the content type is selected in the settings
2. Check that the user does not have a bypass role
3. Clear the Drupal cache
4. Check the access logs in the report page
5. Verify the correct paths are being accessed

## Support

For issues or feature requests, please create an issue in the module's issue queue. 