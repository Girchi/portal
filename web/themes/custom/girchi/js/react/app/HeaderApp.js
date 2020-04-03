import React, { useEffect, useState, useContext } from "react";
import Axios from "axios";
import NotificationWrapper from "./NotificationWrapper/NotificationWrapper";
import { AppContext } from "./AppContext";

const HeaderApp = ({ accessToken, refreshToken, socket }) => {
    const [notifications, setNotifications] = useState([]);
    const { state, dispatch } = useContext(AppContext);
    const decrement = () => dispatch({ type: "decrement" });
    const increment = () => dispatch({ type: "increment" });
    const ENDPOINT = process.env.REACT_APP_ENDPOINT;
    const getNotifications = () => {
        Axios.get(`${ENDPOINT}notifications/user`, {
            withCredentials: true
        }).then(
            res => {
                setNotifications(res.data.notifications);
            },
            err => console.log(err)
        );
    };

    useEffect(() => {
        socket.on("notification added", notification => {
            setNotifications(currentNotifications => [
                notification,
                ...currentNotifications
            ]);
            increment();
        });
        socket.on("rerender notification", ({ _id }) => {
            decrement();
        });
    }, []);

    useEffect(() => {
        if (notifications.length > 5) {
            setNotifications(currentNotifications =>
                currentNotifications.slice(0, -1)
            );
        }
    }, [notifications]);
    useEffect(() => {
        getNotifications();
    }, [state]);
    return (
        <NotificationWrapper notifications={notifications} socket={socket} />
    );
};

export default HeaderApp;
