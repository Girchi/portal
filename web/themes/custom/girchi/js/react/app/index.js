import React, { useState } from "react";
import ReactDOM from "react-dom";
import HeaderApp from "./HeaderApp";
import App from "./App";
import "regenerator-runtime";
import { generateJwtIfExpired } from "./Utils/Utils";
import io from "socket.io-client";
import { AppContextProvider } from "./AppContext";

const ENDPOINT = process.env.REACT_APP_ENDPOINT;

const accessToken = generateJwtIfExpired();
if (accessToken) {
    let socket = io(ENDPOINT);

    socket.emit("auth", { accessToken }, err => {
        console.log(err);
    });

    ReactDOM.render(
        <AppContextProvider>
            <HeaderApp accessToken={accessToken} socket={socket} />
        </AppContextProvider>,
        document.getElementById("notifications-header")
    );
    const element = document.getElementById("notifications");
    if (typeof element != "undefined" && element != null) {
        ReactDOM.render(
            <AppContextProvider>
                <App socket={socket} accessToken={accessToken} />
            </AppContextProvider>,
            element
        );
    }
}
