
importScripts('https://www.gstatic.com/firebasejs/8.4.3/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.4.3/firebase-messaging.js');

var firebaseConfig = {
    apiKey: '__VUE_APP_FIREBASE_API_KEY__',
    authDomain: '__VUE_APP_FIREBASE_AUTH_DOMAIN__',
    projectId: '__VUE_APP_FIREBASE_PROJECT_ID__',
    storageBucket: '__VUE_APP_FIREBASE_STORAGE_BUCKET__',
    messagingSenderId: '__VUE_APP_FIREBASE_MESSAGING_SENDER_ID__',
    appId: '__VUE_APP_FIREBASE_APP_ID__',
    measurementId: '__VUE_APP_FIREBASE_MEASUREMENT_ID__'
}

firebase.initializeApp(firebaseConfig)

const messaging  = firebase.messaging()
messaging.onBackgroundMessage((msg) => {
    console.log("tesing sevice worker",msg)
});
