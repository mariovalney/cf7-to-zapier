# Contributing

Guidelines for working on the project.

## Commit

We standardize the way of writing commits. The goal is to create **messages that are more readable** and that easily pass the project's history.

* Be succinct.
* Always write a title and if necessary a message explaining what was done.
* Writing the reason about a change is better than what was done in the code.
* Standardized language: **English**.

### Formatting

````
[Tag] Relevant title

Commit message. Usually explaining what has changed,
removed or added and possible implementation details
that can be used by the team in future development.
````

### Exemplo de Tags

* **Feat:** A new feature
* **Fix:** Fixing a bug
* **Style:** Change on writing the code
* **Refact:** Refactoring stuff
* **Docs:** Documentation
* **Test:** About tests
* **Build:** About building flow

## Coding Standards

WordPress Coding Standards, with spaces.

## Git

You should use `git rebase master` before merge.

It keeps the git timeline beautiful.

And please, create a Issue about your PR.

## Development

### Templates

Templates are Header / Body values.

To create new templates, just add ir to `docs/templates.json`:

```
{
    "name": "Your App or Template Name",
    "headers": [ "x-example: custom-value", "x-example-2: custom-value-2" ]
    "body": "{ "example": "New message from [your-name] - [your-message]", "data": "__VALUES__" }"
    "docs": "https://wordpress.org/plugins/cf7-to-zapier/",
    "separator": " | "
}
```

* Name - Your template name.
* Headers - Array of strings to be added to "Headers" options.
* Body - The value to "Body" option In general, a JSON string.
* Docs - A URL to documentation.
* Separator - When using "__VALUES__" you can change the fields separator for your example.

Note: Only "Name" and "Body" are required.

As you don't know the fields from user, we use the "__VALUES__" placeholder.

The plugin you add all user fields separated by " | " by default (or using "separator" value).
