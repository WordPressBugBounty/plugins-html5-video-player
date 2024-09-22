// ; (function ($) {
//     $(document).ready(function () {

// console.log('working from google js');
// The Browser API key obtained from the Google API Console.
// Replace with your own Browser API key, or your own key.
var developerKey = h5vpGoogleApi.apikey;
const isPro = h5vpGoogleApi.plugin == "pro";

// The Client ID obtained from the Google API Console. Replace with your own Client ID.
var clientId = h5vpGoogleApi.client_id;

//client secret id: v5BUxnmMaAUyC1qN86tdtX0w

// Replace with your own project number from console.developers.google.com.
// See "Project number" under "IAM & Admin" > "Settings"
var appId = h5vpGoogleApi.project_number;

// Scope to use to access user's Drive items.
var scope = ["https://www.googleapis.com/auth/drive.file"];

var pickerApiLoaded = false;
var oauthToken;

function openPicker() {
  if (appId == "" || "" == clientId || "" == developerKey || !isPro) {
    document.getElementById("google_empty_alert").style.display = "block";
  } else {
    gapi.load("auth", { callback: onAuthApiLoad });
    gapi.load("picker", { callback: onPickerApiLoad });
  }
  return false;
}

// Use the Google API Loader script to load the google.picker script.
// function loadPicker() {
//     gapi.load('auth', { 'callback': onAuthApiLoad });
//     gapi.load('picker', { 'callback': onPickerApiLoad });
// }

function onAuthApiLoad() {
  window.gapi.auth.authorize(
    {
      client_id: clientId,
      scope: scope,
      immediate: false,
    },
    handleAuthResult
  );
}

function onPickerApiLoad() {
  pickerApiLoaded = true;
  createPicker();
}

function handleAuthResult(authResult) {
  if (authResult && !authResult.error) {
    oauthToken = authResult.access_token;
    createPicker();
  }
}

// Create and render a Picker object for searching images.
function createPicker() {
  // console.log("createPicker");
  if (pickerApiLoaded && oauthToken) {
    // console.log("loaded and have auth token");
    var view = new google.picker.View(google.picker.ViewId.DOCS);
    //view.setMimeTypes("image/png,image/jpeg,image/jpg");
    var picker = new google.picker.PickerBuilder()
      .enableFeature(google.picker.Feature.NAV_HIDDEN)
      //.enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
      .setAppId(appId)
      .setOAuthToken(oauthToken)
      .addView(view)
      .addView(new google.picker.DocsUploadView())
      .setDeveloperKey(developerKey)
      .setCallback(pickerCallback)
      .build();
    picker.setVisible(true);
  }
}

// A simple callback implementation.
function pickerCallback(data) {
  if (data.action == google.picker.Action.PICKED) {
    // console.log(data);
    var embedUrl = data.docs[0].embedUrl;
    document.getElementById("h5vp_google_document_url").value = embedUrl;
  }
}

//     });
// })(jQuery);
