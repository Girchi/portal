import React, { useEffect, useState, useRef } from "react";
import Axios from "axios";
import NotificationWrapper from "./NotificationWrapper/NotificationWrapper";
import AppContext from "./AppContext";

const HeaderApp = ({ accessToken, refreshToken, socket }) => {
    const [notifications, setNotifications] = useState([]);
    const appHook = useState({
        unreadCount: 0
    });

    const getNotifications = () => {
        Axios.get(
            "http://notifications.girchi.docker.localhost/notifications/user",
            {
                withCredentials: true
            }
        ).then(
            res => {
                setNotifications(res.data.notifications);
            },
            err => console.log(err)
        );
    };

    useEffect(() => {
        getNotifications();
        socket.on("notification added", notification => {
            setNotifications(currentNotifications => [
                notification,
                ...currentNotifications
            ]);
        });
    }, []);

    useEffect(() => {
        if (notifications.length > 5) {
            setNotifications(currentNotifications =>
                currentNotifications.slice(0, -1)
            );
        }
    }, [notifications]);
    return (
        <AppContext.Provider value={appHook}>
            <NotificationWrapper notifications={notifications} />
        </AppContext.Provider>
    );
};

export default HeaderApp;
