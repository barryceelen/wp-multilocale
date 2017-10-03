[Experimental] WordPress Multilocale Plugin
===========================================

[![Build Status](https://travis-ci.org/barryceelen/wp-multilocale.svg?branch=master)](https://travis-ci.org/barryceelen/wp-multilocale)

The Multilocale plugin offers a tiny toolset for publishing content in multiple locales. It is intentionally limited in scope, a custom built theme is a requirement, it does not offer localization of all core functionality and will most certainly **break your website** and forever make 🌈🌈double rainbows 🚫disappear.

## Incomplete Todo List

- [ ] Check user caps in admin locale tabs and how to handle them
- [ ] The locale tabs break easily
- [ ] Add per language rewrite rules for post types if the post type slug is i18n enabled
  (Example: a 'book' custom post type: example.com/book/the-smurfs, example.com/de/buch/die-schlumpfe, example.com/nl/boek/de-smurfen.)
- [ ] Add per language rewrite rules for taxonomy archives if the taxonomy slug is i18n enabled. (Example: a 'carrot' taxonomy:
  example.com/carrot/term, example.com/de/moehre/term, example.com/nl/wortel/term.)
- [x] [Allow identical slug for posts in different locales.](https://github.com/barryceelen/wp-multilocale-duplicate-slug)
- [x] [Enable shared attachments between translations.](https://github.com/barryceelen/wp-multilocale/commit/1cd45dac3c248ebe8c3b7c44cf2bfc291537bc58)
- [ ] Store taxonomy term post count per language and use that number in stead. For example one might end up with an incorrect result when using `get_terms()` setting `hide_empy` to true
- [x] Modify parent selector on hierarchical post type admin screen to only include posts from the same locale
- [ ] Add UI to enable changing the locale of a post and/or change its translation group
- [ ] Use Automattic [wp-cldr](https://github.com/Automattic/wp-cldr) plugin if it is available
- [ ] Translate terms
- [ ] Translate attachments
- [ ] Use rewrite rules in stead of modifying \***yikes**\* $_SERVER['REQUEST_URI']😱

### Also

- [ ] Installation routine, what to do with existing content
- [ ] Deactivation/Deletion of plugin, same question
- [ ] Remove a locale, what should happen with content in that locale?
- [ ] ...
