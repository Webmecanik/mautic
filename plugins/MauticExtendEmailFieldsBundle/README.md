# Extend Email Fields bundle

Plugin add new fields to emails.

- support me on <a href="https://mtcextendee.com">mtcextendee.com</a>

## Installation

### Manual 
- Require this PR https://github.com/mautic/mautic/pull/7539
- Download from https://github.com/kuzmany/mautic-extend-email-fields-bundle
- Unzip files to plugins/MauticExtendEmailFieldsBundle
- Go to /s/plugins/reload
- Setup MauticExtendEmailFieldsBundle integration

### Setup

1. Enable integration in plugins
2. Setup labels for extra1 and extra2
![image](https://user-images.githubusercontent.com/462477/57943420-4b1b7000-78d4-11e9-9197-bb90305e371f.png)
3. Go to add/edit email and see new custom fields 
![image](https://user-images.githubusercontent.com/462477/57943495-74d49700-78d4-11e9-9a00-ba76afa66b2f.png)
4. Go to reports and you can use extra1 and extra2 fields for columns
![image](https://user-images.githubusercontent.com/462477/57943605-bc5b2300-78d4-11e9-9dd5-9b435cc01e55.png)

### Rector

`bin/rector process  --config plugins/MauticExtendEmailFieldsBundle/rector.yaml`

### API

```
$api = new \Mautic\Api\Api($auth, $apiUrl);
$response = $api->makeRequest('extendemailfields/'.$emailId, ['extra1'=>'custom text for extra 1'], 'POST');
```

### Credits

<div>Icons made by <a href="https://www.flaticon.com/authors/pixel-perfect" title="Pixel perfect">Pixel perfect</a></div>



