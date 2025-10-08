module.exports = {
	content:[
		'/app/resources/views/**/*.php',
		'/app/resources/assets/**/*.js',
		'/app/resources/assets/**/*.css',
		'/modules/**/src/resources/views/**/*.php',
		'/modules/**/src/resources/assets/**/*.js',
		'/modules/**/src/resources/assets/**/*.css',
	],
	theme: {
		extends: {},
	},
  plugins: [require("@tailwindcss/forms"), require("@tailwindcss/typography")],
};