<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only create function if using PostgreSQL
        if (config('database.default') !== 'pgsql') {
            return;
        }

        DB::unprepared("
            CREATE OR REPLACE FUNCTION upsert_candidate(
                p_session_id TEXT,
                p_field      TEXT,
                p_answer     TEXT,
                p_step       INT
            ) RETURNS TABLE(done BOOLEAN, candidate JSONB) AS \$\$
            DECLARE 
                v_cid INT;
                v_array_text TEXT[];
            BEGIN
                -- 1) sessie → kandidaat (één kandidaat per sessie)
                -- Use email as session identifier
                IF NOT EXISTS (SELECT 1 FROM candidates WHERE email = p_session_id) THEN
                    INSERT INTO candidates (email, created_at, updated_at) 
                    VALUES (p_session_id, NOW(), NOW()) 
                    RETURNING id INTO v_cid;
                    
                    -- Ensure candidate_texts record exists
                    INSERT INTO candidate_texts (candidate_id) 
                    VALUES (v_cid)
                    ON CONFLICT (candidate_id) DO NOTHING;
                ELSE
                    SELECT id INTO v_cid FROM candidates WHERE email = p_session_id;
                END IF;

                -- 2) map veldnamen → kolommen
                IF p_field = 'first_name' THEN 
                    UPDATE candidates SET first_name = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'last_name' THEN 
                    UPDATE candidates SET last_name = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'birth_date' OR p_field = 'date_of_birth' THEN 
                    UPDATE candidates 
                    SET date_of_birth = CASE 
                        WHEN p_answer ~ '^\\d{4}-\\d{2}-\\d{2}' THEN p_answer::date
                        WHEN p_answer ~ '^\\d{2}-\\d{2}-\\d{4}' THEN to_date(p_answer, 'DD-MM-YYYY')
                        ELSE NULL
                    END, 
                    updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'email' THEN 
                    UPDATE candidates SET email = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'phone' THEN 
                    UPDATE candidates SET phone = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'city' THEN 
                    UPDATE candidates SET city = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'postal_code' THEN 
                    UPDATE candidates SET postal_code = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'work_permission_nl' THEN 
                    UPDATE candidates 
                    SET work_permission_nl = (LOWER(p_answer) IN ('ja', 'yes', 'true', '1')), 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'availability' THEN
                    IF LOWER(p_answer) LIKE '%direct%' OR LOWER(p_answer) LIKE '%per direct%' OR LOWER(p_answer) LIKE '%meteen%' THEN 
                        UPDATE candidates 
                        SET availability_type = 'per_direct', 
                            availability_date = NULL, 
                            notice_weeks = NULL,
                            updated_at = NOW() 
                        WHERE id = v_cid;
                    ELSIF p_answer ~ '\\d{2}-\\d{2}-\\d{4}' OR p_answer ~ '\\d{4}-\\d{2}-\\d{2}' THEN 
                        UPDATE candidates 
                        SET availability_type = 'datum', 
                            availability_date = CASE 
                                WHEN p_answer ~ '^\\d{2}-\\d{2}-\\d{4}' THEN to_date(p_answer, 'DD-MM-YYYY')
                                WHEN p_answer ~ '^\\d{4}-\\d{2}-\\d{2}' THEN p_answer::date
                                ELSE NULL
                            END,
                            notice_weeks = NULL,
                            updated_at = NOW() 
                        WHERE id = v_cid;
                    ELSE 
                        UPDATE candidates 
                        SET availability_type = 'opzegtermijn', 
                            notice_weeks = NULLIF(regexp_replace(p_answer, '\\D', '', 'g'), '')::INT,
                            availability_date = NULL,
                            updated_at = NOW() 
                        WHERE id = v_cid;
                    END IF;
                    
                ELSIF p_field = 'hours_per_week' THEN 
                    UPDATE candidates 
                    SET hours_per_week = NULLIF(regexp_replace(p_answer, '\\D', '', 'g'), '')::INT, 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'work_mode' THEN 
                    UPDATE candidates 
                    SET work_mode = CASE 
                        WHEN LOWER(p_answer) LIKE '%locatie%' OR LOWER(p_answer) LIKE '%kantoor%' THEN 'locatie'
                        WHEN LOWER(p_answer) LIKE '%hybride%' OR LOWER(p_answer) LIKE '%hybrid%' THEN 'hybride'
                        WHEN LOWER(p_answer) LIKE '%remote%' OR LOWER(p_answer) LIKE '%thuis%' THEN 'remote'
                        ELSE LOWER(p_answer)
                    END, 
                    updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'primary_titles' THEN 
                    -- Convert comma-separated string to JSON array
                    v_array_text := string_to_array(p_answer, ',');
                    UPDATE candidates 
                    SET primary_titles = to_jsonb(v_array_text), 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'sectors' THEN 
                    -- Convert comma-separated string to JSON array
                    v_array_text := string_to_array(p_answer, ',');
                    UPDATE candidates 
                    SET sectors = to_jsonb(v_array_text), 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'experience_years' THEN 
                    UPDATE candidates 
                    SET experience_years = NULLIF(regexp_replace(p_answer, '\\D', '', 'g'), '')::INT, 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'last_responsibilities' THEN 
                    UPDATE candidate_texts 
                    SET last_responsibilities = p_answer 
                    WHERE candidate_id = v_cid;
                    
                ELSIF p_field = 'top_skills' THEN 
                    -- Convert comma-separated string to JSON array
                    v_array_text := string_to_array(p_answer, ',');
                    UPDATE candidate_texts 
                    SET top_skills = to_jsonb(v_array_text) 
                    WHERE candidate_id = v_cid;
                    
                ELSIF p_field = 'tools_tech' THEN 
                    -- Convert comma-separated string to JSON array
                    v_array_text := string_to_array(p_answer, ',');
                    UPDATE candidate_texts 
                    SET tools_tech = to_jsonb(v_array_text) 
                    WHERE candidate_id = v_cid;
                    
                ELSIF p_field = 'education_level' THEN 
                    UPDATE candidates 
                    SET education_level = CASE 
                        WHEN LOWER(p_answer) LIKE '%mbo%' OR LOWER(p_answer) LIKE '%vocational%' THEN 'vocational'
                        WHEN LOWER(p_answer) LIKE '%hbo%' OR LOWER(p_answer) LIKE '%bachelor%' THEN 'bachelor'
                        WHEN LOWER(p_answer) LIKE '%wo%' OR LOWER(p_answer) LIKE '%master%' THEN 'master'
                        WHEN LOWER(p_answer) LIKE '%phd%' OR LOWER(p_answer) LIKE '%doctoraat%' THEN 'phd'
                        WHEN LOWER(p_answer) LIKE '%middelbaar%' OR LOWER(p_answer) LIKE '%high_school%' THEN 'high_school'
                        ELSE LOWER(p_answer)
                    END, 
                    updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'salary' OR p_field = 'salary_expectation' THEN 
                    UPDATE candidates 
                    SET salary_expectation = NULLIF(regexp_replace(p_answer, '[^0-9\\.]', '', 'g'), '')::NUMERIC, 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'travel' OR p_field = 'travel_radius' THEN
                    UPDATE candidates 
                    SET travel_radius_km = NULLIF(regexp_replace(p_answer, '\\D', '', 'g'), '')::INT,
                        drivers_license = (POSITION('ja' IN LOWER(p_answer)) > 0 OR POSITION('yes' IN LOWER(p_answer)) > 0),
                        updated_at = NOW()
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'languages' THEN
                    -- Convert comma-separated string to JSON array
                    v_array_text := string_to_array(p_answer, ',');
                    UPDATE candidates 
                    SET languages = to_jsonb(v_array_text), 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'employer_values' THEN 
                    UPDATE candidate_texts 
                    SET employer_values = p_answer 
                    WHERE candidate_id = v_cid;
                    
                ELSIF p_field = 'best_result' THEN 
                    UPDATE candidate_texts 
                    SET best_result = p_answer 
                    WHERE candidate_id = v_cid;
                    
                ELSIF p_field = 'consent' OR p_field = 'consent_retention' THEN 
                    UPDATE candidates 
                    SET consent_retention_months = CASE 
                        WHEN LOWER(p_answer) IN ('ja', 'yes', 'true', '1') THEN 12 
                        ELSE 0 
                    END, 
                    updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'notify_new_roles' THEN 
                    UPDATE candidates 
                    SET notify_new_roles = (LOWER(p_answer) IN ('ja', 'yes', 'true', '1')), 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                END IF;

                -- Return result with candidate data
                RETURN QUERY
                    SELECT 
                        (p_step >= 23) AS done,
                        (
                            SELECT row_to_json(c)::jsonb
                            FROM (
                                SELECT * FROM candidates WHERE id = v_cid
                            ) c
                        ) AS candidate;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS upsert_candidate(TEXT, TEXT, TEXT, INT)');
        }
    }
};
