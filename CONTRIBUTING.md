# Contributing

Thank you for considering contributing to Event Bridge For ActivityPub WordPress Plugin 💜

You can contribute in the following ways:

- Finding and reporting bugs
- Translating the plugin into various languages
- Contributing code by fixing bugs or implementing features
- Improving the documentation
- Proposing ideas for new features

## Bug reports

Bug reports and feature suggestions must use descriptive and concise titles and be submitted to [Codeberg Issues](https://codeberg.org/Event-Federation/wordpress-event-bridge-for-activitypub/issues) or the [WordPress.org Support Forum](https://wordpress.org/support/plugin/event-bridge-for-activitypub/). Please use the search function to make sure that you are not submitting duplicates, and that a similar report or request has not already been resolved or rejected.

## Translations

You can submit translations via [translate.WordPress.org](https://translate.wordpress.org/projects/wp-plugins/event-bridge-for-activitypub/). These changes are merged by WordPress itself to each plugin installation.

## Pull requests

**Please use clean, concise titles for your pull requests.** Unless the pull request is about refactoring code or other internal tasks, assume that the person reading the pull request title is not a programmer or WordPress developer, but instead a WordPress user, and **try to describe your change or fix from their perspective**. We use commit squashing, so the final commit in the main branch will carry the title of the pull request, and commits from the main branch are fed into the changelog. The changelog is separated into [keepachangelog.com categories](https://keepachangelog.com/en/1.0.0/), and while that spec does not prescribe how the entries ought to be named, for easier sorting, start your pull request titles using one of the verbs "Add", "Change", "Deprecate", "Remove", or "Fix" (present tense).

Example:

| Not ideal                            | Better                                                        |
| ------------------------------------ | ------------------------------------------------------------- |
| Fixed NoMethodError in RemovalWorker | Fix nil error when removing statuses caused by race condition |

It is not always possible to phrase every change in such a manner, but it is desired.

**The smaller the set of changes in the pull request is, the quicker it can be reviewed and merged.** Splitting tasks into multiple smaller pull requests is often preferable.

**Pull requests that do not pass automated checks may not be reviewed**. In particular, you need to keep in mind:

- Unit and integration test (PHPUnit)
- Code style rules (PHP_CodeSniffer)
- [WordPress plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

## Documentation

- Documentation for users is currently in the README.md and readme.txt files and the admin pages of the plugin (see the `.template/` folder), although this can change if it could be advantageous.
- Documentation for developers is the the projects [Wiki](https://codeberg.org/Event-Federation/wordpress-event-bridge-for-activitypub/wiki).
