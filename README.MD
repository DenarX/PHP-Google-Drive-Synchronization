# PHP Google Drive Synchronization

Based on Google API library

`composer require google/apiclient:^2.11`

Required credentials.json - created in Google Cloud Platform
- Create Project
- Explore and enable APIs
- Google Drive API -> enable
- Credentials -> Create Service Account
- Service Account -> Keys -> Add key -> JSON
- download and rename it to credentials.json and place to folder with sync.php
- share your folder on Google Drive with created Service Account with Editor permisiions

## Simple usage

```
<?php
require_once 'sync.php';
new sync('files','XXXX-XXXXXXX_XXXXX-XXXXXXXXXXXXXX');
```
