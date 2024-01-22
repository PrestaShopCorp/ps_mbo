## Usage

```
try {
    dump($this->get('mbo.modules.helper')->findForUpdates('productcomments'));
} catch (\Exception $e) {
    ErrorHelper::reportError($e);
    throw $e;
}
```

### Return values

```
[
  "current_version" => "5.0.2"
  "available_version" => "6.0.2"
  "upgrade_available" => true,
  'urls' => [
      'install' => null,
      'upgrade' => '/manage/action/upgrade/productcomments',
   ]
]
```
If the module is installed and do have an upgrade


============================================================

```
[
  "current_version" => "5.0.2"
  "available_version" => "5.0.2"
  "upgrade_available" => false,
  'urls' => [
      'install' => null,
      'upgrade' => null,
   ]
]
```
If the module is installed but doesn't have an upgrade

============================================================

```
[
  "current_version" => null
  "available_version" => "5.0.2"
  "upgrade_available" => false,
  'urls' => [
      'install' => '/manage/action/install/productcomments',
      'upgrade' => null,
   ]
]
```
If the module is not installed but exists on Addons

============================================================

```
[
  "current_version" => null
  "available_version" => null
  "upgrade_available" => false,
  'urls' => [
      'install' => null,
      'upgrade' => null,
   ]
]
```
If the module is not installed and doesn't exist on Addons
