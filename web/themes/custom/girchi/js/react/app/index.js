import React from "react";
import ReactDOM from "react-dom";
import HeaderApp from "./HeaderApp";
import App from "./App";
import "regenerator-runtime";
import Cookies from "js-cookie";
import { generateJwtIfExpired } from "./Utils/Utils";
import io from "socket.io-client";

let accessToken = Cookies.get("g-u-at");
const refreshToken = Cookies.get("g-u-rt");

const ENDPOINT = "http://notifications.girchi.docker.localhost/";
let socket = io(ENDPOINT);
generateJwtIfExpired(accessToken);
accessToken = Cookies.get("g-u-at");
socket.emit("auth", { accessToken, refreshToken }, err => {
    console.log(err);
});

ReactDOM.render(
    <HeaderApp
        accessToken={accessToken}
        refreshToken={refreshToken}
        socket={socket}
    />,
    document.getElementById("notifications-header")
);
// ReactDOM.render(
//     <App socket={socket} accessToken={accessToken} />,
//     document.getElementById("notifications")
// );
