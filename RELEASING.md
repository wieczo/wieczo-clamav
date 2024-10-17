## Releasing a new version of the library

WIP // this document will be used as a todo list for keeping track of the steps necessary to release a new version of the library.

---

The steps necessary to release a new version of the library are as follows:

### GitHub steps

- Update `README.md` 
- Update `CHANGELOG.md`
- Update `readme.txt` for WordPress plugin directory
  - [Official Readme Standards](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)
  - https://generatewp.com/plugin-readme/
  - https://wordpress.org/plugins/developers/readme-validator/
- Tag the current version in Git
- Create a GitHub release

### WordPress Plugin steps

- Download the release as a zip
- Extract the zip and remove some files
  - remove the .gitignore file
  - remove the .github folder
  - remove all other hidden files
- Checkout the plugin from WordPress' SVN
- Delete all files in the /trunk folder
- Put the extracted files in it
- SVN Commit
- SVN Tag:
  - Create a tag with SVN in the /tags folder of the SVN repo