Configuration Reference
=======================

    jihel_viking_pay:
        accounts:
            default:
                password: %viking_pass%
                userId: %viking_user%
                entityId: %viking_entity_id%

- accounts:
    **name**: Replace by any name. Remember tu give it to the instruction as extended data !! Default is `default`
        - password: Viking pay password
        - userId: Viking pay userId
        - entityId: The  reference to your MID
