import React, { useState, useEffect, useContext } from "react";
import NotificationFP from "./NotificationFP/NotificationFP";
import { generateJwtIfExpired } from "./Utils/Utils";
import Axios from "axios";
import { AppContext } from "./AppContext";

const App = ({ socket, accessToken }) => {
    const { state, dispatch } = useContext(AppContext);
    const [notifications, setNotifications] = useState([]);
    const [page, setPage] = useState(1);
    const ENDPOINT = process.env.REACT_APP_ENDPOINT;
    const decrement = () => dispatch({ type: "decrement" });

    const handleScroll = () => {
        const windowHeight =
            "innerHeight" in window
                ? window.innerHeight
                : document.documentElement.offsetHeight;
        const body = document.body;
        const html = document.documentElement;
        const docHeight = Math.max(
            body.scrollHeight,
            body.offsetHeight,
            html.clientHeight,
            html.scrollHeight,
            html.offsetHeight
        );
        const windowBottom = windowHeight + window.pageYOffset;
        if (windowBottom + 10 >= docHeight) {
            setPage(currentPage => currentPage + 1);
        }
    };
    const getNotifications = () => {
        Axios.get(`${ENDPOINT}notifications/user/?page=${page}`, {
            withCredentials: true
        }).then(
            res => {
                setNotifications(notifications.concat(res.data.notifications));
            },
            err => console.log(err)
        );
    };
    async function readNotification(_id) {
        Axios(`${ENDPOINT}notifications/${_id}/read`, {
            method: "post",
            withCredentials: true
        }).then(
            res => {
                socket.emit("notification read", { _id }, err => {
                    console.log(err);
                });
            },
            err => console.log(err)
        );
    }

    useEffect(() => {
        window.addEventListener("scroll", handleScroll);
        socket.on("notification added", notification => {
            setNotifications(currentNotifications => [
                notification,
                ...currentNotifications
            ]);
        });
        socket.on("rerender notification", ({ _id }) => {
            setNotifications(currentNotifications => {
                const res = currentNotifications.map(notf =>
                    notf._id === _id ? { ...notf, isRead: true } : notf
                );
                return res;
            });
        });
    }, []);

    useEffect(() => {
        getNotifications();
    }, [page]);

    useEffect(() => {}, [notifications]);

    return (
        <div className="card-body p-0">
            {notifications.length > 0 ? (
                notifications.map(notification => (
                    <NotificationFP
                        notification={notification}
                        readNotification={readNotification}
                        key={notification._id}
                    />
                ))
            ) : (
                <div className="notifications__notifi-box__item">
                    <a>
                        <div className="notifications__notifi-box__item__text">
                            <h4>თქვენ არ გაქვთ შეტყობინება</h4>
                        </div>
                    </a>
                </div>
            )}
        </div>
    );
};

export default App;
