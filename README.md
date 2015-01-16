Library
=======

Layout
------

Provides the basic page structure, including the following blocks:

- title
- sidebar
- breadcrumb
- panelContent
    - panelTitle
    - panelRest
- modal

Tool
----

Extends the main layout and uses special variables to initialize
some parts. For example, the template:

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
- *toolPage*: path of the current page of the tool
- *toolWorkspace*: name of the workspace of the tool (workspace section only)
