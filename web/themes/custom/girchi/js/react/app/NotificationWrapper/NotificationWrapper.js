import React, { useState, useEffect, useContext, useRef } from "react";
import Notification from "../Notification/Notification";
import Axios from "axios";
import { AppContext } from "../AppContext";
import NotificationBell from "../NotificationBell/NotificationBell";
import useOutsideAlerter from "../Hooks/useOutsideAlerter";

const NotificationWrapper = ({ notifications, socket }) => {
    const { state, dispatch } = useContext(AppContext);
    const [showBox, setShowBox] = useState(false);
    const wrapperRef = useRef(null);
    const toggleBox = () => {
        setShowBox(!showBox);
    };
    const decrement = () => dispatch({ type: "decrement" });

    useOutsideAlerter(wrapperRef, () => setShowBox(false));

    const ENDPOINT = process.env.REACT_APP_ENDPOINT;

    async function readNotification(_id) {
        Axios(`${ENDPOINT}notifications/${_id}/read`, {
            method: "post",
            withCredentials: true
        }).then(
            res => {
                socket.emit("notification read", { _id }, err => {
                    console.log(err);
                });
                decrement();
            },
            err => console.log(err)
        );
    }
    return (
        <div ref={wrapperRef}>
            <NotificationBell toggleBox={toggleBox} />
            {showBox && (
                <div className="notifications__notifi-box">
                    <div className="notifications__notifi-box__triangle"></div>

                    {notifications.length > 0 ? (
                        notifications.map(notification => (
                            <Notification
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
                    <div className="notifications__notifi-box__item">
                        <a
                            className="d-flex justify-content-center align-items-center notifications__notifi-box__item__text"
                            href="/notifications"
                        >
                            მეტის ნახვა{" "}
                            <span>
                                <i className="ml-3 icon-arrow-right2"></i>
                            </span>
                        </a>
                    </div>
                </div>
            )}
        </div>
    );
};

export default NotificationWrapper;
