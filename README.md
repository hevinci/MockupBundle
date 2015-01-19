Installation
------------

`composer require hevinci/mockup-bundle dev-master`

Usage
-----

1. Create a *mockup* directory in the "Resource/views" directory 
of your bundle. Alternatively, you can do the same in the *app*
directory of your application:
<pre>
    app
        Resources
            MyVendorMyBundle
                views
                    mockup
</pre>

2. Create some mockups within this directory. Mockups are basically twig
templates, which might take advantage of the provided library (see below) 
if needed.

3. Use the *mockup* route with the *path* query parameter to render a 
particular mockup. For example, the url for rendering the template
`FooBarBundle::mockup/test.html.twig` will be:
<div>
    `http://localhost/myproject/mockup?path=foobar/test.html`
</div>    
Note that:
    - the path must be prefixed by the name of the bundle in short,
      lower case notation
    - the *mockup* path segment must be omitted
    - the *.twig* extension must be omitted  

4. Use the `hevinci:mockup:export` command to produce a standalone version
of your mockup(s). That command will generate a package containing static
html files and all the required assets.
Currently this command accept as argument either a bundle name (e.g. 
`FooBarBundle`) or a template reference (e.g. 
`FooBarBundle::mockup/test.html.twig`)


Library
-------

### *claroline/layout.html.twig*

Provides the basic structure of a claroline page, including the 
following blocks:

- title
- sidebar
- breadcrumb
- panelContent
    - panelTitle
    - panelRest
- modal

### *claroline/tool.html.twig*

Provides a tool layout. Dedicated variables are used to render 
automatically some parts of the page. For example, the template:

```django

{% extends 'HeVinciMockupBundle::library/tool.html.twig' %}

{% set toolName = 'My tool' %}
{% set toolSection = 'Administration' %}
{% set toolPage = ['Users', 'Management'] %}

```

will be rendered as the *Users / Management* page of an administration tool
called "My tool".

Complete list of available variables:

- *toolName*: name of the tool
- *toolSection*: platform section ("Administration", "Bureau" or "Espace d'activités")
- *toolIcon*: short name of the font-awesome icon of the tool
- *toolPage*: path of the current page of the tool (breadcrumb)
- *toolWorkspace*: name of the workspace of the tool (workspace section only)

TODO
----

- Add a "mockup map" functionality, allowing to order mockups and 
  easily navigate through them
- Allow to export a particular mockup directory
- Extend the library