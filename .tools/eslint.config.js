import prettier from 'eslint-config-prettier';
import pluginPrettier from 'eslint-plugin-prettier';

export default [
  {
    ignores: [
		"**/node_modules/**",
		"project/.tools/**",
		"project/www/js/inc/**",
		"project/chat/inc/**"
    ],
  },
  {
    files: ["**/*.js"],
    languageOptions: {
      ecmaVersion: "latest",
      sourceType: "module"
    },
    plugins: {
      prettier: pluginPrettier
    },
    rules: {
      "prettier/prettier": "error"
    }
  },
  prettier
];
