
import "firebase/app"
import "firebase/messaging"
import firebase from "firebase/compat";

let m = false;

function isBadIosXX() {
	var regex = /(iPhone|iPad|iPod);[^OS]*OS (\d)/;
	var matches = navigator.userAgent.match(regex);

	if (!matches) return false;
	return true;
}

console.log(isBadIosXX());

const firebaseConfig = {
	apiKey: process.env.VUE_APP_FIREBASE_API_KEY,
	authDomain: process.env.VUE_APP_FIREBASE_AUTH_DOMAIN,
	projectId: process.env.VUE_APP_FIREBASE_PROJECT_ID,
	storageBucket: process.env.VUE_APP_FIREBASE_STORAGE_BUCKET,
	messagingSenderId: process.env.VUE_APP_FIREBASE_MESSAGING_SENDER_ID,
	appId: process.env.VUE_APP_FIREBASE_APP_ID,
	measurementId: process.env.VUE_APP_FIREBASE_MEASUREMENT_ID,
}

if(window.location.protocol==='https:' && !isBadIosXX()){

	firebase.initializeApp(firebaseConfig)
	m = firebase.messaging();

}
export default m
