# Direktt Service Status

It is tightly integrated with the [Direktt WordPress Plugin](https://wordpress.org/plugins/direktt/).  

With Service Status extension you can:

- **Create and Manage Service Cases** as a serviceperson either using Direktt mobile app or wp-admin.
- **Display active cases & history** to users in Direktt mobile app.
- **Send customizable notifications** to users to notify them of new service case creation or case status changes.
- **Review full case history** for every serviceperson using Direktt mobile app or wp-admin.

## Documentation

You can find the detailed plugin documentation, guides and tutorials in the Wiki section:  
https://github.com/direktt/direktt-service-status/wiki

## Requirements

- WordPress 5.6 or higher
- The [Direktt Plugin](https://wordpress.org/plugins/direktt/) (must be active)

## Installation

1. Install and activate the **Direktt** core plugin.
2. Download the direktt-service-status.zip from the latest [release](https://github.com/direktt/direktt-service-status/releases)
3. Upload **direktt-service-status.zip** either through WordPress' **Plugins > Add Plugin > Upload Plugin** or upload the contents of this direktt-service-status.zip to the `/wp-content/plugins/` directory of your WordPress installation.
4. Activate **Direktt Service Status** from your WordPress plugins page.
5. Create Service Case Statuses in wp-admin **Direktt > Service Case Status**
6. Configure the plugin under **Direktt > Settings > Service Status Settings**.

## Usage

### Plugin Settings

- Find **Direktt > Settings > Service Status Settings** in your WordPress admin menu.
- Configure:
    - Direktt user category/tag allowed to manage service cases.
    - Notifications for users on new case creation and case status change.
    - Opening (first in chain ) and closing status (last in chain).

- Find **Direktt > Service Case Status**
- Configure:
    - Set up all service statuses in your workflow **(e.g. "Waiting for inspection", "Inspected, service order created, waiting for service", "Service in progress", "Service finished, ready for pick up", "Closed")**.

### Case Management

- Add service case via wp-admin/Direktt User profile/shortcode.
- Set the case title and case description (optional).
- If you are adding service case via wp-admin or shortcode, you will need to enter user's Subscription ID.
- Edit service cases via wp-admin/Direktt User profile/shortcode.
- All actions are logged in the user’s **service status change log**.

### Shortcode (Front End)

Show the all non-closed cases (only to Direktt Admin and users that are able top manage cases) and current user's non-closed cases to Direktt user:

```[direktt_service_case]```

## Notification Templates

Direktt Message templates support following dynamic placeholders:

- `#case-no#` — title of the service case
- `#date-time#` — timestamp when case was opened or status was changed
- `#old-status#` for old status (only for case status change message template)
- `#new-status#` for new status (only for case status change message template)

## Case Status Change Logs

For every case status creating or change, an entry is made with admin name (not visible to user), subscription id (not visible to usr), old status, new status and timestamp.

---

## Updating

The plugin supports updating directly from this GitHub repository.

---

## License

GPL-2.0-or-later

---

## Support

Contact [Direktt](https://direktt.com/) for questions, issues, or contributions.
