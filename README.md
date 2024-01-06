# Jira Issue Viewer: CSV to HTML

This repo provides a simple, efficient way to transform CSV files into HTML format. All content will be in a read-only state. Descriptions and Comments should be formatted, although it's not perfect. If set up correctly, all content should be able to be displayed offline, including attachments.

The interface allows you to be able to view a list of the issues and select any to view its details. You'll find information like the issue Name, Type, Status, Priority, Environment, Description, Attachments, Creator, Created Date, Labels, Parent, and Comments.

## Usage

1. Clone or download this repo

1. Place on server that can run PHP. I used XAMPP on Windows.

1. In Jira, view your issues and set any filters you want. Then click the "Export" button and select "CSV" to download it.

1. You'll want to download the attachments too. You can download all attachments in Jira by creating a cloud backup in your settings, then downloading the backup. Attachments are in the `data/attachments` folder.

1. Place the CSV file in the same directory as the index.php file and make sure it is named `jira.csv`.

1. Duplicate the `.env.example` file and rename it to `.env`. Then update the values to match your Jira instance. By default, user IDs will display in the HTML rather than names, but you can map the IDs to names in the `.env` file.

1. Open the `index.php` file in your browser. If using XAMPP, you likely need to go to `http://localhost/repo-name/`.

## Additional Notes

- Only tested manually with my own Jira instance. Just wanted to share in case it helps anyone else.
- For the issues export, it seems like you are limited to 1000 issues at a time, so you may need to export multiple times and combine the CSV files.