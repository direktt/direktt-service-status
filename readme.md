# Direktt Service Status

Direktt Service Status is a WordPress plugin that empowers you to create, manage, and track service cases directly from the Direktt mobile app or WordPress admin. Fully integrated with the [Direktt mobile app](https://direktt.com/), it keeps clients informed with automated notifications at every step, while offering real-time access to active cases and history—all fully integrated with your existing Direktt workflow. Perfect for any service-based business aiming to boost transparency, accountability, and client satisfaction.

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
    - Opening (first in chain) and closing service status (last in chain).

- Find **Direktt > Service Case Status**
- Configure:
    - Set up all service statuses in your workflow **(e.g. "Waiting for inspection", "Inspected, service order created, waiting for service", "Service in progress", "Service finished, ready for pick up", "Closed")**.

### Workflow

- **Creation of service case**
    - Customer **requests a service** (landscaping, carpet cleaning, construction services...) or **drops off an item for service** (car, bicycle, skis...)
    - Serviceperson **creates new service case** via Direktt mobile app or wp-admin.
    - Customer receives initial automated message related to creation of service case.
- **Service status changes**
    - As the service progresses through stages, serviceperson **sets respective service case statuses** using Direktt mobile app or wp-admin.
    - Customer receives automated message on each status change all up to the completion of service (closing service status).
- **User wants to check current service status**
    - User can always check current service status and related service history using Direktt mobile app  
- All actions are logged in the customer’s **service status change log**.

### Shortcode (Front End)

```[direktt_service_case]```

Using this shortcode, you can display the following:
- Current user's non-closed cases and their service case history **(customers)**
- All non-closed cases with the service history **(channel admins and servicepersons)**

## Notification Templates

Direktt Message templates support following dynamic placeholders:

- `#case-no#` — title of the service case
- `#date-time#` — timestamp when case was opened or status was changed
- `#old-status#` for old status (only for case status change message template)
- `#new-status#` for new status (only for case status change message template)

## Case Status Change Logs

For every case status creation or change, a log entry is created with the reference to user who initiated the action, subscription id, old status, new status and timestamp.

---

## Updating

The plugin supports updates directly from WordPress admin console.  

You can find all plugin releases in the Releases section of this repository:  
https://github.com/direktt/direktt-service-status/releases.

---

## License

GPL-2.0-or-later

---

## Support

Please use Issues section of this repository for any issue you might have:  
https://github.com/direktt/direktt-service-status/issues.  

Join Direktt Community on Discord - [Direktt Discord Server](https://discord.gg/xaFWtbpkWp)  

Contact [Direktt](https://direktt.com/) for general questions, issues, or contributions.
