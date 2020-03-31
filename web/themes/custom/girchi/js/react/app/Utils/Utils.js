import jwt from "jsonwebtoken";
import Axios from "axios";

export const generateJwtIfExpired = accessToken => {
    if (accessToken) {
        const { exp } = jwt.decode(accessToken);
        if (new Date().getTime() > exp * 1000) {
            Axios.get("/api/jwt/token_refresh").then(
                res => console.log(res),
                error => console.log(error)
            );
        } else {
        }
    }
};
