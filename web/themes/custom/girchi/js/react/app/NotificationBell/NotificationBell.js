import React, { useState, useEffect, useContext } from "react";
import { AppContext } from "../AppContext";
import Axios from "axios";

const NotificationBell = ({ toggleBox }) => {
    const { state, dispatch } = useContext(AppContext);
    const { unreadCount } = state;
    const setUnreadCount = unreadCount =>
        dispatch({ type: "setUnreadCount", payload: unreadCount });
    const ENDPOINT = process.env.REACT_APP_ENDPOINT;

    useEffect(() => {
        Axios.get(`${ENDPOINT}notifications/user/unread-count`, {
            withCredentials: true
        }).then(
            res => {
                setUnreadCount(res.data.count);
            },
            err => console.log(err)
        );
    }, [unreadCount]);
    return (
        <div className="notifications__icon" onClick={() => toggleBox()}>
            <img src="themes/custom/girchi/images/Bell.svg" />
            {state.unreadCount > 0 && <span>{state.unreadCount}</span>}
        </div>
    );
};

export default NotificationBell;
