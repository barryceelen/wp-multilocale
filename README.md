# [Experimental] WordPress Multilocale Plugin

Publish content in multiple locales.

The Multilocale plugin offers a tiny toolset for publishing content in multiple locales. It is intentionally limited in scope, a custom built theme is a requirement, it does not offer localization of all core functionality and will most certainly **break your website** and forever make 🌈🌈double rainbows 🚫disappear.

## Incomplete Todo List

- Check user caps in admin locale tabs and how to handle them.
- The locale tabs break easily.
- Add per language rewrite rules for post types if the post type slug is i18n enabled.
  (Example: a 'book' custom post type: mysite.com/book/the-smurfs, mysite.com/de/buch/die-schlumpfe, mysite.com/nl/boek/de-smurfen.)
- Add per language rewrite rules for taxonomy archives if the taxonomy slug is i18n enabled. (Example: a 'carrot' taxonomy:
  mysite.com/carrot/term, mysite.com/de/moehre/term, mysite.com/nl/wortel/term.)
- Allow identical slug for posts in different locales.
- Enable shared attachments between translations.
- Store taxonomy term post count per language and use that number in stead. For example one might end up with an incorrect result when using get_terms() setting hide_empy to true.
- ~~Modify parent selector on hierarchical post type admin screen to only include posts from the same locale.~~
- Add UI to enable changing the locale of a post and/or change its translation group.

### Also

- Installation routine, what to do with existing content.
- Deactivation/Deletion of plugin, same question.
- Remove a locale, what should happen with content in that locale?
- ...
