<?php

// Query SQL untuk Total Kehadiran
$sql = "SELECT 
            c.class_name,
            COUNT(s.id) as total_sessions,
            (SELECT COUNT(*) FROM attendance a 
             JOIN class_sessions cs ON a.session_id = cs.id 
             WHERE cs.class_id = c.id AND a.status = 'Present') as total_present
        FROM classes c
        LEFT JOIN class_sessions s ON c.id = s.class_id
        GROUP BY c.id";