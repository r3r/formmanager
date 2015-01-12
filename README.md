# formmanager

##Description
formmanager is intended to be a complete form management system with the following concepts and use cases:
* A form is an entity that contains elements as attributes.
* A form can have multiple views that expose only specific fields of the forms
* There is an API which allows users to access the data collected by the form and perform basic search operations.

##Design Overview
formmanager's backend is explicitly separated from frontend.
* The backend is exposed via a RESTful interface (endpoint backend/<component>/<controller>)
* The frontend is an angular app which consumes the RESTful interface. (url : frontend/index.html)

##Project Status
Still in development.

