Bolt Forms
==========

Bolt Forms is an interface to Symfony Forms for Bolt.  It provides a Twig template function and 
exposes a simplified API for extending as you need.

Template Use
------------

Define a form in `app/config/extensions/boltforms.bolt.yml` and add the following to your template:

```twig
{{ boltforms('formname') }}
```

Template with data
------------------

You can also add parameters to the BoltForms invocation in Twig. In this case the value for the field "textfieldname" will be pre-set "fieldvalue"

```twig
{{ boltforms('formname', 'Some text before the form', 'After the form', { textfieldname: "fieldvalue"}) }}
```

Fields
------

Each field contains an `options` key that is an array of values that is passed directly to 
Symfony Forms.  See [the Symfony documentation](http://symfony.com/doc/current/reference/forms/types/form.html) for more information. 

```yaml
    fieldname:
      type: text
      required: true
      options:
        label: My Field
        attr:
          placeholder: Enter your details…
        constraints: [ NotBlank, {Length: {'min': 3}} ]
```

Choice Types
------------

Choice types in BoltForms provide three different options for choice values. 
The standard indexed and associative arrays, and Bolt specific Contenttype 
record lookups.

```yaml
  fields:
    array_index:
      type: choice
      options:
        choices: [ Yes, No ]
    array_assoc:
      type: choice
      options:
        choices: { kittens: 'Fluffy Kittens', puppies: 'Cute Puppies' }
    lookup:
      type: choice
      options:
        choices: 'contenttype::pages::title::slug'
```

For the Bolt Contenttype options choices, you just need to make a string with 
double-colon delimiters, where:
    'contenttype' - String constant that always equals 'contenttype'
    'name'        - Name of the contenttype itself
    'labelfield'  - Field to use for the UI displayed to the user
    'valuefield'  - Field to use for the value stored

File Upload Types
-----------------

Handling file uploads is a very common attack vector used to compromise (hack)
a server.

BoltForms does a few things to help increase slightly the security of handling
file uploads.

The following are the "global" options that apply to all form uploads:

```yaml
uploads:
  enabled: true                             # The global on/off switch for upload handling
  base_directory: /data/customer-uploads/   # Outside web root and writable by the web server's user
  filename_handling: prefix                 # Can be either "prefix", "suffix", or "keep"
  management_controller: true               # Enable a controller to handle browsing and downloading of uploaded files
```

The directory that you specify for `base_directory` should **NOT** be a route 
accessible to the outside world. BoltForms provides a special route should you 
wish to make the files browsable after upload. This route can be enabled as a 
global setting via the `management_controller` option.

Secondly, is the `filename_handling` parameter is an important for your level
of required site security. This is important, as if an attacker knows the 
uploaded file name, this can make their job a bit easier. BoltForms provides
three uploaded file naming options, `prefix`, `suffix` and `keep`. 

e.g. uploading the file `kitten.jpg`:

-------------------------------------
| Setting   | Resulting file name     |
|-----------|-------------------------|
| `prefix` | kitten.Ze1d352rrI3p.jpg |
| `suffix` | kitten.jpg.Ze1d352rrI3p |
| `keep`   | kitten.jpg              |

 
We recommend `suffix` as this is the most secure, alternatively `prefix` will
aid in file browsing. However, `keep` should always be used with caution!

Each form has individual options for uploads, such as whether to attach the 
uploaded file in the notification message, or whether to place the uploaded file
in a separate subdirectory or the given global upload target. 
 
A very basic, and cut-down, example of a form with an upload field type is given
here:

```yaml
file_upload_form:
  notification:
    enabled: true
    attach_files: true             # Optionally send the file as an email attachment
  uploads:
    subdirectory: file_upload_dir  # Optional subdirectory
  fields:
    upload:
      type: file
      options:
        required: false
        label: Picture of your pet that you want us to add to our site

```

File Upload Browsing
--------------------

When `management_controller` is enabled, a file in the `base_directory` 
location is accessible via `http://your-site.com/boltforms/download?file=filename.ext`.

These files can be listed via the Twig function `boltforms_uploads()`, e.g.  

```twig
{{ boltforms_uploads() }}
```

This can be limited to a form's (optionally defined) subdirectory by passing the
form name into `boltforms_uploads()`, e.g.

```twig
{{ boltforms_uploads('file_upload_form') }}
```

Redirect after submit
---------------------

On successfull submit the user can be redirected to another Bolt page, or URL. 
The page for the redirect target must exist.

The redirect is added to the `feedback` key of the form, for example: 

```yaml
  feedback:
    success: Form submission successful
    error: There are errors in the form, please fix before trying to resubmit
    redirect:
      target: page/another-page  # A page path, or URL
      query: [ name, email ]     # Optional keys for the GET parameters
```

**Note:**
* `target:` — Either a route in the form of `contenttype/slug` or a full URL
* `query:` — (optional) Either an indexed, or associative array
  - `[ name, email ]` would create the query string `?name=value-of-name-field&email=value-of-email-field`
  - `{ name: 'foo', email: 'bar' }` would create the query string `?name=foo&email=bar`

API
---

Below is a brief example of how to implement the Bolt Forms API.  For a slightly 
more detailed example, see the `Bolt\Extension\Bolt\BoltForms\Twig\BoltFormsExtension` 
class.

```php
// Get the API class
$forms = $this->app['boltforms'];

// Make the forms object inside the API class
$forms->makeForm($formname, 'form', $options, $data);

// Your array of file names and properties.
// See config.yml.dist for examples of the array properties
$fields = array();

// Add our fields all at once
$forms->addFieldArray($formname, $fields);

if ($app['request']->getMethod() == 'POST') {
    $formdata = $forms->handleRequest($formname);
    $sent = $forms->getForm($formname)->isSubmitted();

    if ($formdata) {
        // The form is both submitted and validated at this point
    }
}
``` 

Event Dispatcher Listener
-------------------------

```php
use Bolt\Extension\Bolt\BoltForms\Event\BoltFormsEvents;

$this->app['dispatcher']->addListener(BoltFormsEvents::POST_SUBMIT,  array($this, 'myPostSubmit'));
```
