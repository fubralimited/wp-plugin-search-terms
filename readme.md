# Search Terms Plugin

---

### How-to:

- Run Composer to update the dependencies (**uaparser** and **geocoder**):

				curl -s https://getcomposer.org/installer | php --
				php composer.phar self-update
				php composer.phar update

- This plugin is hookless, so you have to call it manually. First activate the plugin on the dashboard and then put the code:

				get_search_term(<word2store>, <total_results>);

- On Wordpress Dashboard you'll get an options menu that querys the DB and shows the results for everyone to see... :wink: :wink:
- Enjoy a cup of coffee, it's good for you.

