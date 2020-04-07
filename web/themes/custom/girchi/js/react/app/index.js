import React, { useState } from "react";
import ReactDOM from "react-dom";
import HeaderApp from "./HeaderApp";
import App from "./App";
import "regenerator-runtime";
import { generateJwtIfExpired } from "./Utils/Utils";
import io from "socket.io-client";
import { AppContextProvider } from "./AppContext";

const ENDPOINT = process.env.REACT_APP_ENDPOINT;
generateJwtIfExpired().then(accessToken => {
    if (accessToken) {
        let socket = io(ENDPOINT, { transports: ["websocket"] });
        socket.emit("auth", { accessToken }, err => {
            console.log(err);
        });
        ReactDOM.render(
            <AppContextProvider>
                <HeaderApp accessToken={accessToken} socket={socket} />
            </AppContextProvider>,
            document.getElementById("notifications-header")
        );
        ReactDOM.render(
            <AppContextProvider>
                <HeaderApp accessToken={accessToken} socket={socket} />
            </AppContextProvider>,
            document.getElementById("notifications-sticky")
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
});
