--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9
-- Dumped by pg_dump version 16.9

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: academic_years; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.academic_years (
    id integer NOT NULL,
    year_start date NOT NULL,
    year_end date NOT NULL,
    semester integer NOT NULL,
    is_active boolean DEFAULT false,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT academic_years_semester_check CHECK ((semester = ANY (ARRAY[1, 2, 3])))
);


ALTER TABLE public.academic_years OWNER TO postgres;

--
-- Name: academic_years_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.academic_years_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.academic_years_id_seq OWNER TO postgres;

--
-- Name: academic_years_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.academic_years_id_seq OWNED BY public.academic_years.id;


--
-- Name: admin; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.admin (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    pass_word character varying(255) NOT NULL,
    is_active boolean DEFAULT true
);


ALTER TABLE public.admin OWNER TO postgres;

--
-- Name: admin_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.admin_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.admin_id_seq OWNER TO postgres;

--
-- Name: admin_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.admin_id_seq OWNED BY public.admin.id;


--
-- Name: advisor; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.advisor (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    pass_word character varying(255) NOT NULL,
    files character varying(255),
    images character varying(255),
    is_active boolean DEFAULT true,
    email_notifications_enabled boolean DEFAULT true,
    email_preferences json,
    advisor_id character varying(50),
    email character varying(255),
    department character varying(255),
    gender character varying(10),
    address text
);


ALTER TABLE public.advisor OWNER TO postgres;

--
-- Name: advisor_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.advisor_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.advisor_id_seq OWNER TO postgres;

--
-- Name: advisor_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.advisor_id_seq OWNED BY public.advisor.id;


--
-- Name: chapter_settings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.chapter_settings (
    id integer NOT NULL,
    group_id integer,
    chapter_name character varying(50) NOT NULL,
    parts text,
    rubric_file_path character varying(255),
    rubric_original_filename character varying(255),
    due_date timestamp without time zone,
    extension_date timestamp without time zone,
    early_bonus_points numeric(5,2) DEFAULT 0,
    late_deduction_points numeric(5,2) DEFAULT 0,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone DEFAULT now()
);


ALTER TABLE public.chapter_settings OWNER TO postgres;

--
-- Name: chapter_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.chapter_settings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.chapter_settings_id_seq OWNER TO postgres;

--
-- Name: chapter_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.chapter_settings_id_seq OWNED BY public.chapter_settings.id;


--
-- Name: coordinator; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.coordinator (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    pass_word character varying(255) NOT NULL,
    is_active boolean DEFAULT true,
    email_notifications_enabled boolean DEFAULT true,
    email_preferences json
);


ALTER TABLE public.coordinator OWNER TO postgres;

--
-- Name: coordinator_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.coordinator_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.coordinator_id_seq OWNER TO postgres;

--
-- Name: coordinator_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.coordinator_id_seq OWNED BY public.coordinator.id;


--
-- Name: database_backups; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.database_backups (
    id integer NOT NULL,
    backup_name character varying(255) NOT NULL,
    file_path text NOT NULL,
    file_size bigint,
    backup_type character varying(50) DEFAULT 'manual'::character varying,
    created_by integer,
    status character varying(50) DEFAULT 'pending'::character varying,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.database_backups OWNER TO postgres;

--
-- Name: database_backups_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.database_backups_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.database_backups_id_seq OWNER TO postgres;

--
-- Name: database_backups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.database_backups_id_seq OWNED BY public.database_backups.id;


--
-- Name: error_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.error_logs (
    id integer NOT NULL,
    error_type character varying(100) NOT NULL,
    error_message text NOT NULL,
    error_file character varying(255),
    error_line integer,
    user_id integer,
    ip_address character varying(45),
    stack_trace text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.error_logs OWNER TO postgres;

--
-- Name: error_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.error_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.error_logs_id_seq OWNER TO postgres;

--
-- Name: error_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.error_logs_id_seq OWNED BY public.error_logs.id;


--
-- Name: group_milestones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.group_milestones (
    id integer NOT NULL,
    group_id integer NOT NULL,
    proposal_status character varying(20) DEFAULT 'missing'::character varying,
    final_defense_status character varying(20) DEFAULT 'missing'::character varying,
    copyright_status character varying(20) DEFAULT 'missing'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    applied_copyright_status character varying DEFAULT 'pending'::character varying,
    research_presented_status character varying DEFAULT 'pending'::character varying,
    research_published_status character varying DEFAULT 'pending'::character varying,
    copyright_approved_status character varying DEFAULT 'pending'::character varying,
    CONSTRAINT group_milestones_copyright_status_check CHECK (((copyright_status)::text = ANY ((ARRAY['missing'::character varying, 'completed'::character varying])::text[]))),
    CONSTRAINT group_milestones_final_defense_status_check CHECK (((final_defense_status)::text = ANY ((ARRAY['missing'::character varying, 'completed'::character varying])::text[]))),
    CONSTRAINT group_milestones_proposal_status_check CHECK (((proposal_status)::text = ANY ((ARRAY['missing'::character varying, 'completed'::character varying])::text[])))
);


ALTER TABLE public.group_milestones OWNER TO postgres;

--
-- Name: group_milestones_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.group_milestones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.group_milestones_id_seq OWNER TO postgres;

--
-- Name: group_milestones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.group_milestones_id_seq OWNED BY public.group_milestones.id;


--
-- Name: group_sdgs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.group_sdgs (
    id integer NOT NULL,
    group_id integer NOT NULL,
    sdg_id integer NOT NULL,
    assigned_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.group_sdgs OWNER TO postgres;

--
-- Name: group_sdgs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.group_sdgs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.group_sdgs_id_seq OWNER TO postgres;

--
-- Name: group_sdgs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.group_sdgs_id_seq OWNED BY public.group_sdgs.id;


--
-- Name: group_thrusts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.group_thrusts (
    id integer NOT NULL,
    group_id integer NOT NULL,
    thrust_id integer NOT NULL,
    assigned_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.group_thrusts OWNER TO postgres;

--
-- Name: group_thrusts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.group_thrusts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.group_thrusts_id_seq OWNER TO postgres;

--
-- Name: group_thrusts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.group_thrusts_id_seq OWNED BY public.group_thrusts.id;


--
-- Name: groups; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.groups (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    leader_id integer,
    research_title text,
    title_status character varying(20) DEFAULT 'pending'::character varying,
    advisor_id integer,
    adviser_id integer,
    sdg_id integer,
    thrust_id integer,
    proposal_file_path character varying(255),
    proposal_original_filename character varying(255),
    proposal_uploaded_at timestamp without time zone,
    final_defense_file_path character varying(255),
    final_defense_original_filename character varying(255),
    final_defense_uploaded_at timestamp without time zone,
    copyright_file_path character varying(255),
    copyright_original_filename character varying(255),
    copyright_uploaded_at timestamp without time zone,
    applied_copyright_file_path character varying,
    applied_copyright_original_filename character varying,
    applied_copyright_uploaded_at timestamp without time zone,
    research_presented_file_path character varying,
    research_presented_original_filename character varying,
    research_presented_uploaded_at timestamp without time zone,
    research_published_file_path character varying,
    research_published_original_filename character varying,
    research_published_uploaded_at timestamp without time zone,
    copyright_approved_file_path character varying,
    copyright_approved_original_filename character varying,
    copyright_approved_uploaded_at timestamp without time zone,
    title_proposal_file character varying(255),
    title_proposal_filename character varying(255),
    title_submitted_at timestamp without time zone,
    title_approval_comment text,
    title_approved_at timestamp without time zone,
    title_approved_by integer
);


ALTER TABLE public.groups OWNER TO postgres;

--
-- Name: groups_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.groups_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.groups_id_seq OWNER TO postgres;

--
-- Name: groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.groups_id_seq OWNED BY public.groups.id;


--
-- Name: programs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.programs (
    id integer NOT NULL,
    code character varying(20) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.programs OWNER TO postgres;

--
-- Name: programs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.programs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.programs_id_seq OWNER TO postgres;

--
-- Name: programs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.programs_id_seq OWNED BY public.programs.id;


--
-- Name: progress; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.progress (
    id integer NOT NULL,
    research_id integer,
    dateuploaded date,
    uploaded_file character varying(100),
    progress_percent numeric(5,2)
);


ALTER TABLE public.progress OWNER TO postgres;

--
-- Name: progress_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.progress_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.progress_id_seq OWNER TO postgres;

--
-- Name: progress_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.progress_id_seq OWNED BY public.progress.id;


--
-- Name: report_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.report_logs (
    id integer NOT NULL,
    generated_by integer NOT NULL,
    report_type character varying(50),
    generated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.report_logs OWNER TO postgres;

--
-- Name: report_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.report_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.report_logs_id_seq OWNER TO postgres;

--
-- Name: report_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.report_logs_id_seq OWNED BY public.report_logs.id;


--
-- Name: research_statuses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.research_statuses (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    color character varying(7) DEFAULT '#6c757d'::character varying,
    is_active boolean DEFAULT true,
    display_order integer DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.research_statuses OWNER TO postgres;

--
-- Name: research_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.research_statuses_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.research_statuses_id_seq OWNER TO postgres;

--
-- Name: research_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.research_statuses_id_seq OWNED BY public.research_statuses.id;


--
-- Name: research_thrusts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.research_thrusts (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    advisor_id integer
);


ALTER TABLE public.research_thrusts OWNER TO postgres;

--
-- Name: research_thrusts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.research_thrusts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.research_thrusts_id_seq OWNER TO postgres;

--
-- Name: research_thrusts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.research_thrusts_id_seq OWNED BY public.research_thrusts.id;


--
-- Name: student; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.student (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    school_id character varying(50),
    program character varying(50) NOT NULL,
    pass_word character varying(255) NOT NULL,
    files character varying(255),
    images character varying(255),
    group_id integer,
    research_title text,
    is_leader boolean DEFAULT false,
    is_active boolean DEFAULT true,
    email character varying(255),
    gender character varying(10),
    address text,
    lastname character varying(100),
    firstname character varying(100),
    middlename character varying(100),
    full_name character varying(300) GENERATED ALWAYS AS ((((((firstname)::text || ' '::text) || (COALESCE(middlename, ''::character varying))::text) || ' '::text) || (lastname)::text)) STORED
);


ALTER TABLE public.student OWNER TO postgres;

--
-- Name: student_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.student_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.student_id_seq OWNER TO postgres;

--
-- Name: student_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.student_id_seq OWNED BY public.student.id;


--
-- Name: system_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.system_logs (
    id integer NOT NULL,
    user_id integer,
    user_type character varying(50),
    action_type character varying(100) NOT NULL,
    description text,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.system_logs OWNER TO postgres;

--
-- Name: system_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.system_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.system_logs_id_seq OWNER TO postgres;

--
-- Name: system_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.system_logs_id_seq OWNED BY public.system_logs.id;


--
-- Name: system_notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.system_notifications (
    id integer NOT NULL,
    notification_type character varying(50) DEFAULT 'system'::character varying,
    recipient_type character varying(50) NOT NULL,
    recipient_id integer,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    priority character varying(20) DEFAULT 'normal'::character varying,
    status character varying(50) DEFAULT 'pending'::character varying,
    created_by integer,
    sent_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.system_notifications OWNER TO postgres;

--
-- Name: system_notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.system_notifications_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.system_notifications_id_seq OWNER TO postgres;

--
-- Name: system_notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.system_notifications_id_seq OWNED BY public.system_notifications.id;


--
-- Name: system_settings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.system_settings (
    id integer NOT NULL,
    setting_key character varying(100) NOT NULL,
    setting_value text,
    setting_type character varying(50) DEFAULT 'text'::character varying,
    description text,
    updated_by integer,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.system_settings OWNER TO postgres;

--
-- Name: system_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.system_settings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.system_settings_id_seq OWNER TO postgres;

--
-- Name: system_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.system_settings_id_seq OWNED BY public.system_settings.id;


--
-- Name: un_sdgs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.un_sdgs (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    advisor_id integer
);


ALTER TABLE public.un_sdgs OWNER TO postgres;

--
-- Name: un_sdgs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.un_sdgs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.un_sdgs_id_seq OWNER TO postgres;

--
-- Name: un_sdgs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.un_sdgs_id_seq OWNED BY public.un_sdgs.id;


--
-- Name: uploads; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.uploads (
    upload_id integer NOT NULL,
    school_id character varying(50) NOT NULL,
    task_name character varying(100) NOT NULL,
    file_path character varying(255) NOT NULL,
    original_filename character varying(255),
    uploaded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying,
    comment text,
    base_grade numeric(5,2),
    final_grade numeric(5,2),
    submission_timing character varying(10)
);


ALTER TABLE public.uploads OWNER TO postgres;

--
-- Name: uploads_upload_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.uploads_upload_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.uploads_upload_id_seq OWNER TO postgres;

--
-- Name: uploads_upload_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.uploads_upload_id_seq OWNED BY public.uploads.upload_id;


--
-- Name: urec_documents; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.urec_documents (
    id integer NOT NULL,
    group_id integer NOT NULL,
    school_id character varying(50),
    document_type character varying(50) NOT NULL,
    file_path character varying(255) NOT NULL,
    original_filename character varying(255) NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying,
    comment text,
    uploaded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    adviser_id integer,
    CONSTRAINT urec_documents_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


ALTER TABLE public.urec_documents OWNER TO postgres;

--
-- Name: urec_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.urec_documents_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.urec_documents_id_seq OWNER TO postgres;

--
-- Name: urec_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.urec_documents_id_seq OWNED BY public.urec_documents.id;


--
-- Name: academic_years id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.academic_years ALTER COLUMN id SET DEFAULT nextval('public.academic_years_id_seq'::regclass);


--
-- Name: admin id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.admin ALTER COLUMN id SET DEFAULT nextval('public.admin_id_seq'::regclass);


--
-- Name: advisor id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.advisor ALTER COLUMN id SET DEFAULT nextval('public.advisor_id_seq'::regclass);


--
-- Name: chapter_settings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chapter_settings ALTER COLUMN id SET DEFAULT nextval('public.chapter_settings_id_seq'::regclass);


--
-- Name: coordinator id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coordinator ALTER COLUMN id SET DEFAULT nextval('public.coordinator_id_seq'::regclass);


--
-- Name: database_backups id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.database_backups ALTER COLUMN id SET DEFAULT nextval('public.database_backups_id_seq'::regclass);


--
-- Name: error_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.error_logs ALTER COLUMN id SET DEFAULT nextval('public.error_logs_id_seq'::regclass);


--
-- Name: group_milestones id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_milestones ALTER COLUMN id SET DEFAULT nextval('public.group_milestones_id_seq'::regclass);


--
-- Name: group_sdgs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_sdgs ALTER COLUMN id SET DEFAULT nextval('public.group_sdgs_id_seq'::regclass);


--
-- Name: group_thrusts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_thrusts ALTER COLUMN id SET DEFAULT nextval('public.group_thrusts_id_seq'::regclass);


--
-- Name: groups id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups ALTER COLUMN id SET DEFAULT nextval('public.groups_id_seq'::regclass);


--
-- Name: programs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.programs ALTER COLUMN id SET DEFAULT nextval('public.programs_id_seq'::regclass);


--
-- Name: progress id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progress ALTER COLUMN id SET DEFAULT nextval('public.progress_id_seq'::regclass);


--
-- Name: report_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_logs ALTER COLUMN id SET DEFAULT nextval('public.report_logs_id_seq'::regclass);


--
-- Name: research_statuses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.research_statuses ALTER COLUMN id SET DEFAULT nextval('public.research_statuses_id_seq'::regclass);


--
-- Name: research_thrusts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.research_thrusts ALTER COLUMN id SET DEFAULT nextval('public.research_thrusts_id_seq'::regclass);


--
-- Name: student id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student ALTER COLUMN id SET DEFAULT nextval('public.student_id_seq'::regclass);


--
-- Name: system_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_logs ALTER COLUMN id SET DEFAULT nextval('public.system_logs_id_seq'::regclass);


--
-- Name: system_notifications id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_notifications ALTER COLUMN id SET DEFAULT nextval('public.system_notifications_id_seq'::regclass);


--
-- Name: system_settings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_settings ALTER COLUMN id SET DEFAULT nextval('public.system_settings_id_seq'::regclass);


--
-- Name: un_sdgs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.un_sdgs ALTER COLUMN id SET DEFAULT nextval('public.un_sdgs_id_seq'::regclass);


--
-- Name: uploads upload_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.uploads ALTER COLUMN upload_id SET DEFAULT nextval('public.uploads_upload_id_seq'::regclass);


--
-- Name: urec_documents id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.urec_documents ALTER COLUMN id SET DEFAULT nextval('public.urec_documents_id_seq'::regclass);


--
-- Data for Name: academic_years; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.academic_years (id, year_start, year_end, semester, is_active, created_at) FROM stdin;
1	2025-09-01	2026-06-30	2	t	2025-12-24 13:04:26.281292
\.


--
-- Data for Name: admin; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.admin (id, name, pass_word, is_active) FROM stdin;
1	Admin	$2y$10$/yoehP7DE0PuYwqXncdq0e8pOOgt95FSHPwOq.VP8YtIajGHkvjee	t
\.


--
-- Data for Name: advisor; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.advisor (id, name, pass_word, files, images, is_active, email_notifications_enabled, email_preferences, advisor_id, email, department, gender, address) FROM stdin;
3	Teacher A	$2y$10$HYNYL2Z7Wz3thnCxnRMWueWSKJR//nrGXDThjQM1yWupkNMhgz8f2	\N	1763697101_milkfish1.jpg	t	t	{"upload_status":true,"title_status":true,"advisor_comments":true,"new_uploads":true,"title_proposals":true,"urec_uploads":true,"system_alerts":true,"backup_notifications":true}	2022	\N	\N	\N	\N
7	Teacher B	$2y$10$GauGeVbXNoHfIM8V4N6Yje4T/l96lAXC82bZTLCDuq4IryDuag/m.	\N	\N	t	t	{"upload_status":true,"title_status":true,"advisor_comments":true,"new_uploads":true,"title_proposals":true,"urec_uploads":true,"system_alerts":true,"backup_notifications":true}	2023	testnessss@gmail.com	DIT	Female	Street Acorn
8	Teacher C	$2y$10$qzRUJw8UHGD5DUhCX2GK7upG7RdtrtcZi/BcUiP8LRKToNECCQqqK	\N	\N	t	t	\N	2021	\N	\N	\N	\N
9	Mr. John	$2y$10$WFnNiTm97naIdu7hSkel7uLhKivACmBMjCxv6Gl4ATEDzf34heNUS	\N	\N	f	t	\N	2019	\N	\N	\N	\N
\.


--
-- Data for Name: chapter_settings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.chapter_settings (id, group_id, chapter_name, parts, rubric_file_path, rubric_original_filename, due_date, extension_date, early_bonus_points, late_deduction_points, created_at, updated_at) FROM stdin;
1	240	Full Manuscript (Chapter 1-5)	[]	\N	\N	\N	\N	0.00	0.00	2026-03-05 09:00:57.879636	2026-03-05 09:07:34.531628
2	240	Chapter 5	[]	\N	\N	2026-03-05 09:07:00	2026-03-05 09:13:00	0.00	0.00	2026-03-05 09:08:17.773885	2026-03-05 09:08:17.773885
3	233	Chapter 5	[]	\N	\N	2026-03-05 09:10:00	2026-03-05 09:12:00	5.00	5.00	2026-03-05 09:10:30.145901	2026-03-05 09:10:30.145901
\.


--
-- Data for Name: coordinator; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.coordinator (id, name, pass_word, is_active, email_notifications_enabled, email_preferences) FROM stdin;
1	Mr. B	$2y$10$jb4ZJNzVQ/CkeG2jlEd9yuunyObc9kxgs7U9vlNxOBL0DDHhPOrOe	f	t	\N
2	Honesto O. Camino	$2y$10$tCdF98q14cDK5XAIMh/ENu1yf4WVerbrofqGXmONmNFK1aAtZm1OK	t	t	\N
\.


--
-- Data for Name: database_backups; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.database_backups (id, backup_name, file_path, file_size, backup_type, created_by, status, notes, created_at) FROM stdin;
4	backup4.sql	../backups/backup4.sql	49008	manual	1	completed		2025-12-25 14:05:38.963746
5	backup4.sql	../backups/backup4.sql	49326	manual	1	completed		2025-12-25 14:05:39.610128
6	backup_2026-02-27.sql	../backups/backup_2026-02-27.sql	256632	manual	1	completed	for this that	2026-02-27 11:09:40.158793
\.


--
-- Data for Name: error_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.error_logs (id, error_type, error_message, error_file, error_line, user_id, ip_address, stack_trace, created_at) FROM stdin;
\.


--
-- Data for Name: group_milestones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.group_milestones (id, group_id, proposal_status, final_defense_status, copyright_status, created_at, updated_at, applied_copyright_status, research_presented_status, research_published_status, copyright_approved_status) FROM stdin;
2	235	completed	missing	missing	2026-01-02 12:55:08.618978	2026-01-02 12:55:08.618978	pending	pending	pending	pending
1	233	completed	completed	completed	2026-01-02 12:53:00.404223	2026-03-04 08:14:32.550513	completed	completed	completed	completed
3	223	completed	completed	completed	2026-01-24 17:20:24.682255	2026-03-04 16:28:17.40575	pending	pending	pending	completed
\.


--
-- Data for Name: group_sdgs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.group_sdgs (id, group_id, sdg_id, assigned_at) FROM stdin;
3	235	3	2025-12-30 12:57:59.792545
4	235	4	2025-12-30 12:57:59.80118
12	233	26	2026-03-05 17:11:39.136364
13	233	18	2026-03-05 17:11:39.145973
\.


--
-- Data for Name: group_thrusts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.group_thrusts (id, group_id, thrust_id, assigned_at) FROM stdin;
\.


--
-- Data for Name: groups; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.groups (id, name, leader_id, research_title, title_status, advisor_id, adviser_id, sdg_id, thrust_id, proposal_file_path, proposal_original_filename, proposal_uploaded_at, final_defense_file_path, final_defense_original_filename, final_defense_uploaded_at, copyright_file_path, copyright_original_filename, copyright_uploaded_at, applied_copyright_file_path, applied_copyright_original_filename, applied_copyright_uploaded_at, research_presented_file_path, research_presented_original_filename, research_presented_uploaded_at, research_published_file_path, research_published_original_filename, research_published_uploaded_at, copyright_approved_file_path, copyright_approved_original_filename, copyright_approved_uploaded_at, title_proposal_file, title_proposal_filename, title_submitted_at, title_approval_comment, title_approved_at, title_approved_by) FROM stdin;
240	Group	\N	Research Flask na basta bastahaah	approved	2023	7	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	uploads/698ece0876ff6_1770966536.pdf	694b8f4997147_1766559561.pdf	2026-03-01 08:40:31.404243		2026-03-01 08:40:53.214574	2
235	Group4-DIT3	\N	Basta Title ito imbedded with Machine Learning	approved	2023	7	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	uploads/699039d07e934_1771059664.pdf	694b8f3b45870_1766559547 (1).pdf	2026-03-02 15:01:26.77729		2026-03-02 15:01:56.564559	2
233	Group2-DIT3	\N	Nadal with AI	approved	2023	7	\N	\N	uploads/69758d3c2afc5_1769311548.png	Screenshot 2026-01-24 214243.png	2026-01-25 11:25:48.182758	uploads/6971d0ed9653c_1769066733.png	Screenshot 2026-01-17 151646.png	2026-01-22 15:25:33.620899	uploads/69733019388de_1769156633.docx	Lesson #.docx	2026-01-23 16:23:53.234759	uploads/699fa2ad6b6bc_1772069549.pdf	Copy of Bea paderog .pdf	2026-02-26 09:32:29.4434	uploads/69a53363b6d5e_1772434275.pdf	Resume.pdf	2026-03-02 14:51:15.752451	uploads/699fa37cbc1f4_1772069756.pdf	694b8f3b45870_1766559547 (1).pdf	2026-02-26 09:35:56.772691	uploads/69a7796883d30_1772583272.pdf	Resume.pdf	2026-03-04 08:14:32.542837	uploads/698ed1d3b44a3_1770967507.pdf	694b8f3b45870_1766559547 (1).pdf	2026-03-02 15:00:44.942093		2026-03-02 15:01:50.715572	2
223	Group1- DIT3	\N	Research Title	approved	2022	3	\N	\N	uploads/69748ed8a433b_1769246424.png	Screenshot 2024-04-20 144028.png	2026-01-24 17:20:24.673657	uploads/69748efaaf41a_1769246458.png	Screenshot 2024-04-20 144028.png	2026-01-24 17:20:58.719033	uploads/69748f0e16203_1769246478.png	Screenshot 2024-04-20 142835.png	2026-01-24 17:21:18.091602	\N	\N	\N	\N	\N	\N	\N	\N	\N	uploads/69a7ed2161a0c_1772612897.pdf	694b8f4997147_1766559561.pdf	2026-03-04 16:28:17.401374	uploads/69a39c5b2b263_1772330075.pdf	COMP-025-Module3.pdf	2026-03-04 08:32:38.156471		2026-03-04 16:27:37.849721	2
250	Group2	\N	Title na bulok	approved	2021	8	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	2026-02-25 08:39:01.093		2026-02-25 08:40:25.527612	2
\.


--
-- Data for Name: programs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.programs (id, code, name, description, is_active, created_at) FROM stdin;
2	DIT	Diploma in Information Technology	Diploma Information Technology Program	t	2025-12-24 13:22:35.287008
1	BSIT	Bachelor of Science in Information Technology	Bachelor Information Technology Program	t	2025-12-24 13:22:35.287008
3	BEED	Bachelor in Elementary Education	Bachelor in Elementary Education Program	t	2026-02-06 13:39:15.809547
\.


--
-- Data for Name: progress; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.progress (id, research_id, dateuploaded, uploaded_file, progress_percent) FROM stdin;
\.


--
-- Data for Name: report_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.report_logs (id, generated_by, report_type, generated_at) FROM stdin;
1	2	status	2025-12-24 10:44:25.980582
2	2	sdg	2025-12-24 10:44:41.153735
3	2	full	2025-12-24 10:46:23.762134
4	2	thrust	2025-12-24 10:46:35.782485
5	2	status	2025-12-26 18:19:49.781385
6	2	status	2026-01-07 11:58:54.807638
7	2	status	2026-01-13 07:47:02.344372
8	2	status	2026-02-04 08:54:57.075018
9	2	full	2026-02-10 20:50:54.184554
10	2	status	2026-02-10 21:04:36.030392
11	2	thrust	2026-02-10 21:04:45.442835
12	2	full	2026-02-10 21:04:50.581432
13	2	sdg	2026-02-10 21:05:01.713781
14	2	status	2026-02-10 21:05:13.518979
15	2	status	2026-02-10 21:08:43.90119
16	2	status	2026-02-11 04:45:32.771574
17	2	full	2026-02-14 21:06:21.890185
18	2	status	2026-02-14 21:10:29.296434
19	2	status	2026-02-16 11:03:36.018958
\.


--
-- Data for Name: research_statuses; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.research_statuses (id, name, description, color, is_active, display_order, created_at) FROM stdin;
5	Completed	Research completed	#6c757d	t	5	2025-12-24 13:04:04.781275
1	Proposal	Research proposal stage	#007bff	f	1	2025-12-24 13:04:04.781275
4	Final Defense	Ready for final defense	#28a745	f	4	2025-12-24 13:04:04.781275
6	Full Manuscript	Full Manuscript uploaded	#febc86	t	0	2026-03-02 16:06:54.562573
2	Chapter 1-3	Working on chapters 1-3	#ffc107	t	2	2025-12-24 13:04:04.781275
3	Chapter 4-5	Working on chapters 4-5	#17a2b8	t	3	2025-12-24 13:04:04.781275
\.


--
-- Data for Name: research_thrusts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.research_thrusts (id, name, description, created_at, advisor_id) FROM stdin;
16	Food Security and Safety	\N	2026-03-02 14:03:30.316446	\N
17	Health and Wellness	\N	2026-03-02 14:03:38.44977	\N
18	Disaster Risk Reduction and Climate Change	\N	2026-03-02 14:03:47.874238	\N
19	Biodiversity and Natural Resources	\N	2026-03-02 14:03:55.832452	\N
20	Energy	\N	2026-03-02 14:04:04.734013	\N
21	Water Resources	\N	2026-03-02 14:04:31.615156	\N
22	Poverty Reduction and Social Development	\N	2026-03-02 14:04:40.238938	\N
23	Culture, Arts, and Humanities	\N	2026-03-02 14:04:48.586244	\N
24	Governance and Institutions	\N	2026-03-02 14:04:58.578097	\N
25	Information and Communications Technology	\N	2026-03-02 14:05:30.440474	\N
26	Manufacturing and Industry	\N	2026-03-02 14:05:44.064738	\N
\.


--
-- Data for Name: student; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.student (id, name, school_id, program, pass_word, files, images, group_id, research_title, is_leader, is_active, email, gender, address, lastname, firstname, middlename) FROM stdin;
12	Juan Dela Cruz	2023-00155-UQ-0	BSIT	$2y$10$ylmkG88GGYhYlSkx1EiGoOxBldTziRb42wJ.OM3XfrDNAqri/K8.m	\N	\N	240	\N	f	t	cruz@gmail.com	Male	cruz@gmail.com	Cruz	Juan	Dela
8	Larraine Natalia B Camposano	2023-00156-UQ-0	DIT	$2y$10$e2aQJJDX1MBRABRxgkGWWu/pvoKdG4puu4vmbQTEBD7r1qVLGdeIq	\N	\N	\N	Three Little Pigs	f	t	natz@gmail.com	Female	Unisan	Camposano	Larraine Natalia	B
4	Michael L. Nadal	2023-00178-UQ-0	DIT	$2y$10$144I4UgYHE22187VQkBtVOEuVdyth6uP3xaXCQ92G715MDgbg5mau	\N	1768252012_fdjerd.png	233	\N	t	t	michaelnadal27rocket@gmail.com	Male	Brgy. Danlagan Padre Burgos Quezon	Nadal	Michael	L.
11	Dexell O Jusi	2023-00160-UQ-0	BSIT	$2y$10$WyjA/5vfuwpyiANPlXPSiOXG.P4p6FK75/lPGKaw3fKpy87Djmc4q	\N	\N	235	\N	f	t	jusi@gmail.com	Male	Salvacion	Jusi	Dexell	O
2	Angeli Mae April	2023-00179-UQ-0	DIT	$2y$10$57KClhzxDYFoHDqNykA4MuXmwxsv3o2a0Zd0RlbJEBrPAKtntlcA2	\N	1763688682_IMG_20240117_154226.jpg	223	dasfggghggf	t	t	testness@gmail.com	Female	Street Acorn	April	Angeli	Mae
6	Jayson Pangit Santollanii	2023-00186-UQ-0	DIT	$2y$10$1oS7hjiCFFCsGyKZDfYh6ek.9CtkLGE7XwwM4e9J1mmnNzml9hTku	\N	1766304515_retouch_2024022018394821.jpg	233	\N	f	t	jay@gmail.com	Male	Agdangan	Santollanii	Jayson	Pangit
9	Patrick R. Etenac	2023-00152-UQ-0	DIT	$2y$10$ETF8nZTI.QYDfP1vyOpl4./wVbz4oREtJ2eOLjvMl0XgDM6EPNzw.	\N	\N	233	\N	f	t	etenac@gmail.com	Male	unisan	Etenac	Patrick	R.
10	Leonard C Adik-que	2023-00158-UQ-0	BSIT	$2y$10$fpbHSRMeXgPcC7fjnyQngu6qqgz5cELzJmmnd0hJe3/LDrcuv/ziK	\N	\N	235	\N	t	t	adi@gmail.com	Male	Brgy. Danlagan Padre Burgos Quezon	Adik-que	Leonard	C
13	Jane Z Doe	2023-00144-UQ-0	DIT	$2y$10$J2HGVc/r5j6pDPpSAqaoZuHMOPpU8H8hOSQjNuHvZk139H4zDWtcK	\N	\N	240	\N	t	t	doe@gmail.com	Female	California	Doe	Jane	Z
15	Maria Santos	2025-00002-UQ-0	DIT	$2y$10$/NK3QZCCQWoWNDyPqepc8u1.mtGcsqR.4N8u0yVwrU5WMuhiMVjd6	\N	\N	250	\N	t	f	\N	\N	\N	Test	Test	Test
14	Juan Dela Cruz	2025-00001-UQ-0	BSIT	$2y$10$rYm7WyjxffTdATZdjT46neWT9uMhaI6GY0xtYqHdNL/Y9zo6mdfAS	\N	\N	250	\N	f	f	\N	\N	\N	One	Test	Student
\.


--
-- Data for Name: system_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.system_logs (id, user_id, user_type, action_type, description, ip_address, user_agent, created_at) FROM stdin;
1	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 13:06:42.125312
2	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 13:09:28.629553
3	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 13:09:48.890669
4	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 13:09:51.054457
5	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-25 13:22:17.381538
6	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-25 13:22:55.900544
7	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-25 13:23:24.20414
8	1	admin	settings_update	Updated system settings: backup_frequency, enable_notifications, max_upload_size, session_timeout, site_name	::1	\N	2025-12-25 13:38:39.388908
9	1	admin	settings_update	Updated system settings: backup_frequency, enable_notifications, maintenance_mode, max_upload_size, session_timeout, site_name	::1	\N	2025-12-25 13:38:52.444579
10	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 13:53:22.175103
11	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 13:53:40.948517
12	2	student	upload	Angeli May April uploaded Chapter 3 (DOCUMENT-SUBMISSION-TEMPLATE_292760372.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 13:54:06.841255
13	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 13:54:09.075985
14	1	admin	backup	Admin created database backup: backup4.sql (0.05 MB)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:05:38.966579
15	1	admin	backup	Admin created database backup: backup4.sql (0.05 MB)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:05:39.618225
16	1	admin	notification	Admin sent notification to all: new update	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:10:51.561502
17	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:10:57.364842
18	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:11:28.504524
19	2	student	notification_read	Angeli May April marked notification as read: new update	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:28:16.473636
20	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:30:32.286904
21	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:30:41.982514
22	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:31:40.00855
23	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:52:17.507346
24	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:53:35.265626
25	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:57:08.894639
26	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:57:18.497366
27	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:58:19.33809
28	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 14:59:05.174007
29	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:00:03.508002
30	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:00:37.74299
31	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:00:58.95854
32	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name	::1	\N	2025-12-25 15:01:31.695855
33	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:01:33.465905
34	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:01:46.747507
35	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:01:49.374952
36	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name	::1	\N	2025-12-25 15:05:17.680788
37	1	admin	settings_update	Updated system settings: backup_frequency, enable_notifications, max_upload_size, session_timeout, site_name	::1	\N	2025-12-25 15:05:28.541272
38	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, enable_notifications, maintenance_mode	::1	\N	2025-12-25 15:12:41.596342
39	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:12:47.745127
40	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:13:00.818718
41	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:14:59.109431
42	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:15:17.339151
43	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:15:52.111085
44	1	admin	settings_update	Updated system settings: backup_frequency, maintenance_mode, max_upload_size, session_timeout, site_name, enable_notifications	::1	\N	2025-12-25 15:16:52.500982
45	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:16:56.066611
46	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:17:10.576951
47	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:17:12.615473
48	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:17:26.338435
49	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-25 15:18:20.68002
50	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, enable_notifications, maintenance_mode	::1	\N	2025-12-25 15:45:33.132231
51	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:45:40.018632
52	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:45:54.682793
53	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:45:57.032694
54	1	admin	settings_update	Updated system settings: backup_frequency, maintenance_mode, max_upload_size, session_timeout, site_name, enable_notifications	::1	\N	2025-12-25 15:47:41.328861
55	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:47:45.807162
56	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:47:59.154772
57	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:48:02.552006
58	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:48:20.980002
59	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:48:36.593554
60	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-25 15:48:51.962449
61	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-25 15:51:00.844149
62	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, enable_notifications, maintenance_mode	::1	\N	2025-12-25 15:51:23.522864
63	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-25 15:51:27.897841
64	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 04:47:03.976574
65	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 04:47:17.687087
66	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 04:47:37.83459
67	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 04:48:04.522923
68	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 04:48:19.651902
69	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 04:52:25.962691
70	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 04:57:38.580517
71	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 04:57:51.250997
72	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 05:26:45.022919
73	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 05:27:03.057831
74	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 05:48:14.708324
75	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 05:49:13.806137
76	7	advisor	approve	Ms. Sammy approved upload: Chapter 2 (InternsEvaluation.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 05:53:54.213622
77	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 05:55:04.002473
78	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 05:58:01.084533
79	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 17:24:18.51699
80	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 17:24:32.163705
81	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 17:30:43.35063
82	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 17:31:06.915974
83	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 17:31:11.040378
84	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-26 17:31:42.73606
85	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 17:32:42.459902
86	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 17:33:07.17032
87	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 17:36:01.822055
88	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 17:53:48.363878
89	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 18:10:12.879088
90	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 18:10:46.406012
91	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 18:19:56.996947
92	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 18:36:21.748901
93	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 18:37:39.710373
94	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 18:38:18.116604
95	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 18:38:54.730181
96	3	advisor	reject	Mr. Acord rejected upload: Chapter 3 (DOCUMENT-SUBMISSION-TEMPLATE_292760372.docx)	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 18:49:12.818943
97	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 18:49:41.188721
98	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 18:53:29.870327
99	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-26 19:06:18.921966
100	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 09:31:32.555887
101	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 09:31:51.877937
102	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 09:32:09.375541
103	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 09:35:31.45826
104	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 09:35:47.691598
105	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 09:36:04.92515
106	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 09:37:21.725826
107	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 09:37:53.546094
108	9	student	login	Patrick Canete logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 09:39:32.923414
109	9	student	logout	Patrick Canete logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 09:39:41.100011
110	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-27 09:47:40.827434
111	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-27 10:11:37.882057
112	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 10:11:51.487862
113	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 10:20:31.8109
114	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 10:20:57.027471
115	4	student	upload	Michael L. Nadal uploaded Chapter 3 (Chapter_2_Integration_of_Data_Analytics.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 10:21:14.721691
116	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 10:21:19.603323
117	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 10:21:35.059571
118	7	advisor	reject	Ms. Sammy rejected upload: Chapter 3 (Chapter_2_Integration_of_Data_Analytics.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 10:22:37.330622
119	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 10:45:55.6718
120	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 11:12:02.737975
121	7	advisor	comment	Ms. Sammy added comment to upload: Chapter 3	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 11:17:03.339926
122	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 11:33:23.639841
123	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 16:54:10.801838
124	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 17:00:05.176288
125	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 18:44:02.855891
126	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 18:44:18.534145
127	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 18:44:27.521442
128	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 18:44:45.8896
129	4	student	upload	Michael L. Nadal uploaded Chapter 3 (is a systematic.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 18:45:05.358024
130	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 18:45:08.748297
131	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 18:45:33.674049
132	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-27 18:47:37.734431
133	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-28 08:04:49.719792
134	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-28 08:05:13.515538
135	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-28 08:42:20.354676
136	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-28 08:42:47.731871
137	4	student	upload	Michael L. Nadal uploaded Chapter 3 (is a systematic.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-28 08:43:02.847519
138	4	student	ai_analysis	AI analyzed Chapter 3 (Upload ID: 15)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-28 08:43:02.864095
139	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-28 08:43:12.55667
140	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-28 08:43:36.190827
141	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-29 08:32:53.20026
142	4	student	upload	Michael L. Nadal uploaded Chapter 3 (ethical considerations.pdf)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-29 08:33:15.785133
143	4	student	ai_analysis	AI analyzed Chapter 3 (Upload ID: 16)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-29 08:33:15.812766
144	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 06:15:11.502945
145	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 06:16:11.788977
146	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 06:16:37.635709
147	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 06:17:24.571376
148	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 06:22:03.488371
149	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 07:14:51.813139
150	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 07:15:04.74703
151	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 07:15:17.916599
152	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 07:15:37.062281
153	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 07:20:37.159705
154	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 07:42:38.074955
155	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 07:43:05.729884
156	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 10:51:01.4551
157	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 10:52:09.404706
158	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 10:52:33.506191
159	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 10:52:44.902663
160	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 10:53:05.222838
161	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:37:39.438031
162	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:37:57.378276
163	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:38:05.032726
164	1	admin	settings_update	Updated system settings: backup_frequency, enable_email_notifications, enable_notifications, max_upload_size, session_timeout, site_name, system_email, system_name, maintenance_mode	::1	\N	2025-12-30 11:38:33.458623
165	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:38:41.90981
166	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:38:57.26971
167	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:39:13.917104
168	1	admin	settings_update	Updated system settings: backup_frequency, maintenance_mode, max_upload_size, session_timeout, site_name, system_email, system_name, enable_email_notifications, enable_notifications	::1	\N	2025-12-30 11:39:37.763534
169	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:39:40.195643
170	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:40:00.116517
171	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:41:32.012659
172	1	admin	settings_update	Updated system settings: backup_frequency, enable_notifications, max_upload_size, session_timeout, site_name, system_email, system_name, enable_email_notifications, maintenance_mode	::1	\N	2025-12-30 11:41:57.956166
173	1	admin	notification	Admin sent notification to advisors: Meeting	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:42:37.784586
174	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:42:40.069367
175	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:42:51.349507
176	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:42:56.927888
177	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:43:14.953895
178	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:43:20.962711
179	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:43:30.417604
180	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2025-12-30 11:44:18.099095
181	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 12:54:06.87875
182	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 12:58:21.523593
183	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 12:58:33.392326
184	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 12:59:44.34879
185	10	student	login	Leornard Adique logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 13:12:04.571962
186	10	student	logout	Leornard Adique logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 13:14:53.208888
187	10	student	login	Leornard Adique logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-30 13:15:21.187588
188	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-31 11:19:10.46012
189	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-31 12:02:24.974038
190	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-31 12:12:58.484353
191	7	advisor	approve	Ms. Sammy approved research title for group: Group4-DIT3 - "Basta Title imbedded with AI"	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-31 12:13:05.802376
192	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-31 12:13:17.060623
193	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-31 12:18:25.551155
194	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-31 12:30:00.326843
195	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-31 12:30:20.105302
196	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2025-12-31 12:31:08.550954
197	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 10:46:37.097363
198	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 10:47:16.87715
199	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 13:01:28.2002
200	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 13:01:35.190249
201	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 13:01:54.336926
202	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:40:15.292973
203	10	student	login	Leornard Adique logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:40:28.808831
204	10	student	logout	Leornard Adique logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:41:06.875447
205	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:41:22.854176
206	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:41:40.65723
207	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:42:16.209797
208	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:42:34.605257
209	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:42:42.150755
210	10	student	login	Leornard Adique logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:43:06.896728
211	10	student	upload	Leornard Adique uploaded Chapter 1 (InternsEvaluation.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:43:47.313285
212	10	student	logout	Leornard Adique logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:46:43.34951
213	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:47:21.710622
214	7	advisor	approve	Ms. Sammy approved upload: Chapter 1 (InternsEvaluation.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:47:35.208826
215	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-01 20:48:06.504131
216	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:01:39.674528
217	7	advisor	approve	Ms. Sammy approved upload: Chapter 3 (ethical considerations.pdf)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:02:42.382494
218	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:12:27.092123
219	10	student	login	Leornard Adique logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:12:42.01859
220	10	student	session_timeout	Leornard Adique session expired	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:45:31.471413
221	10	student	login	Leornard Adique logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:45:47.652477
222	10	student	logout	Leornard Adique logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:45:54.715851
223	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:46:16.468451
224	7	advisor	update_milestone	Ms. Sammy updated milestones for group 'Group2-DIT3': Proposal: Completed, Final Defense: Missing, Copyright/IP: Missing	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:53:00.40738
225	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:53:21.336208
226	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:54:04.42356
227	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:54:22.983211
228	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:54:59.67719
229	7	advisor	update_milestone	Ms. Sammy updated milestones for group 'Group4-DIT3': Proposal: Completed, Final Defense: Missing, Copyright/IP: Missing	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-02 12:55:08.620783
230	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2026-01-02 13:00:04.280734
231	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-04 07:21:55.497163
232	4	student	session_timeout	Michael L. Nadal session expired	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-04 08:04:10.425816
233	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-04 08:04:32.677895
234	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-04 08:08:43.225317
235	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-04 08:09:10.942777
236	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-04 08:13:18.430561
237	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-04 08:13:30.706416
238	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-04 08:14:10.991971
239	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-04 08:36:36.650978
240	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-04 08:41:49.629451
241	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-06 09:47:27.349893
242	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-06 09:49:14.680667
243	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-06 09:50:03.786719
244	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-06 09:50:11.877971
245	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:51:29.851146
246	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:52:40.381142
247	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:54:06.000039
248	4	student	upload	Michael L. Nadal uploaded Chapter 4 (Research Monitoring System.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:54:44.748263
249	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:55:36.972295
250	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:55:53.69381
251	7	advisor	comment	Ms. Sammy added comment to upload: Chapter 4	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:56:45.93991
252	7	advisor	reject	Ms. Sammy rejected upload: Chapter 4 (Research Monitoring System.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:56:56.955711
253	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:57:20.938134
254	2	coordinator	login	Mr. Oni logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:57:30.292114
255	2	coordinator	logout	Mr. Oni logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 11:59:17.627022
256	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 12:00:08.396242
257	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 12:01:08.402139
258	1	admin	settings_update	Updated system settings: backup_frequency, maintenance_mode, max_upload_size, session_timeout, site_name, system_email, system_name, enable_email_notifications, enable_notifications	::1	\N	2026-01-07 12:02:41.553017
259	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 12:02:43.465774
260	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 12:03:01.627371
261	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 12:03:08.317184
262	1	admin	settings_update	Updated system settings: backup_frequency, enable_email_notifications, enable_notifications, max_upload_size, session_timeout, site_name, system_email, system_name, maintenance_mode	::1	\N	2026-01-07 12:03:37.151109
263	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 12:03:53.433574
264	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 12:04:08.2563
265	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-07 12:04:12.106768
266	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-08 06:12:12.530954
267	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-08 06:12:31.949881
268	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-08 06:12:41.400775
269	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 04:55:43.145783
270	4	student	upload	Michael L. Nadal uploaded Chapter 4 (Use Case Diagram.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 05:13:29.266904
271	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 06:09:57.750571
272	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 06:10:19.261578
273	7	advisor	comment	Ms. Sammy added comment to upload: Chapter 1	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 06:23:42.896202
274	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 07:13:52.971096
275	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 07:14:12.992717
276	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 07:16:29.549317
277	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 07:16:42.332418
278	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 07:48:58.243201
279	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 07:54:45.24898
280	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 07:54:59.294327
281	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 08:03:14.093433
282	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 08:03:24.587406
283	4	student	session_timeout	Michael L. Nadal session expired	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-13 11:42:18.920613
284	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-15 11:52:55.847367
285	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-15 11:53:05.938581
286	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-15 11:55:51.730351
287	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-15 11:56:08.375217
288	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-18 14:43:29.068121
289	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-18 15:45:55.53535
290	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-18 15:46:20.587233
291	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-18 16:21:15.468419
292	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-18 16:21:31.495046
293	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-18 16:29:32.236169
294	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0	2026-01-18 16:30:02.174089
295	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/143.0.0.0	2026-01-18 16:57:28.303176
296	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 16:12:42.878087
297	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 16:32:54.056788
298	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 16:33:28.613912
299	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 16:37:31.851387
300	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 16:38:38.099829
301	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 16:39:42.542672
302	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 16:40:10.543646
303	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 16:49:35.922385
304	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 16:49:51.590344
305	4	student	session_timeout	Michael L. Nadal session expired	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 17:40:10.672006
306	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 17:40:32.857838
307	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-20 17:41:35.158078
308	1	admin	settings_update	Updated system settings: backup_frequency, enable_email_notifications, max_upload_size, session_timeout, site_name, system_email, system_name, enable_notifications, maintenance_mode	::1	\N	2026-01-20 17:45:47.673464
309	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-21 14:57:14.054856
310	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-22 15:21:26.959532
311	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 06:10:55.803087
312	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 06:59:40.333214
313	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 07:00:07.608957
314	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 07:16:56.131159
315	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 07:17:16.630894
316	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:22:42.499758
317	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:22:54.271655
318	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:23:17.959594
319	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:24:04.623458
320	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:24:17.447237
321	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:24:29.436253
322	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:34:27.508176
323	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:34:39.502917
324	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:35:03.18203
325	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:35:38.312835
326	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:35:52.348334
327	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:36:05.218177
328	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:36:46.992432
329	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-23 16:53:50.489197
330	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-24 07:11:39.863013
331	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 07:22:02.819896
332	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 07:22:22.49079
333	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-24 15:21:55.028361
334	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-24 15:22:06.246544
335	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-24 15:22:22.609871
336	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 15:56:18.797683
337	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 15:56:44.515262
338	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 16:14:45.73394
339	1	admin	settings_update	Updated system settings: backup_frequency, enable_notifications, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-01-24 16:37:24.529547
340	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 16:46:35.426513
341	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 16:47:17.863848
342	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 16:47:36.112868
343	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 16:47:55.810346
344	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 17:18:24.720802
345	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 17:19:19.156358
346	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 17:19:48.794033
347	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 17:20:06.536892
348	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-01-24 17:21:51.03924
349	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-24 17:23:16.593146
350	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 11:25:00.363284
351	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 11:39:21.182359
352	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 11:40:35.596049
353	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 11:46:03.415397
354	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 11:46:26.250643
355	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 11:47:31.845938
356	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 11:47:46.655133
357	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 11:48:01.685757
358	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 11:55:58.525454
359	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 12:01:02.039434
360	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 12:17:08.956169
361	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 12:17:52.399961
362	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 12:32:39.216205
363	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-25 13:00:19.190393
364	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-26 14:22:54.299181
365	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-26 14:56:40.608042
366	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-26 14:56:54.187168
367	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-26 15:09:52.434484
368	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-26 15:10:22.811155
369	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-26 15:13:38.359277
370	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-26 15:13:53.28941
371	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 12:13:58.16613
372	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 12:28:46.923364
373	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 12:28:59.740944
374	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:02:36.07915
375	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:03:02.323479
376	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:11:01.982367
377	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:11:25.833107
378	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:11:39.624688
379	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:11:49.432371
380	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:14:35.753445
381	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:14:50.821348
382	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:22:37.890536
383	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:22:48.115952
384	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:23:04.956775
385	1	admin	settings_update	Updated system settings: backup_frequency, maintenance_mode, max_upload_size, session_timeout, site_name, system_name	::1	\N	2026-01-27 13:24:36.766043
386	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:24:41.212248
387	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:24:59.496677
388	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:25:01.056735
389	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:25:17.075042
390	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-27 13:28:18.854571
391	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 15:22:50.722845
392	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 15:22:52.661983
393	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 15:23:05.428337
394	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-01-29 15:23:55.776279
395	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 15:24:59.208536
396	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 15:25:12.60934
397	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 15:25:25.258507
398	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 15:47:20.81499
399	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 16:00:18.210735
400	2	student	upload	Angeli May April uploaded Chapter 3 (is a systematic.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 16:02:13.959904
401	2	student	upload	Angeli May April uploaded Chapter 3 (Research.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 16:02:42.139944
402	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 16:02:46.534794
403	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 16:03:04.626416
404	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 16:41:46.750062
405	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 16:42:06.641326
406	3	advisor	comment	Mr. Acord added comment to upload: Chapter 3	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 16:44:20.747331
407	3	advisor	approve	Mr. Acord approved upload: Chapter 3 (Research.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 16:44:43.924891
408	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-01-29 16:49:50.298433
409	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-01 13:31:35.583921
410	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-01 13:54:34.223316
411	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-01 13:59:19.516014
412	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-01 14:09:47.399471
413	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-01 14:10:28.086348
414	2	student	upload	Angeli May April uploaded Chapter 4 (Resume.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-01 14:10:44.943877
415	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-01 14:10:50.259949
416	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-01 14:11:14.34133
417	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-01 14:37:05.097069
418	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-01 14:59:32.76014
419	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-02 07:47:08.655965
420	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-02 07:48:09.308052
421	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-02 07:49:42.499526
422	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-02 07:49:57.112912
423	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-02 07:52:11.816023
424	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-02 07:52:27.923051
425	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-02 07:56:28.801612
426	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-02 07:57:10.885521
427	1	admin	settings_update	Updated system settings: backup_frequency, maintenance_mode, max_upload_size, session_timeout, site_name, system_name	::1	\N	2026-02-02 07:57:48.966763
428	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-02 07:57:52.695699
429	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-02 07:58:12.759877
430	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-02 07:58:18.40567
431	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-02 07:59:00.034128
432	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-02 07:59:09.56339
433	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 08:51:27.192067
434	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 08:51:57.743808
435	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 08:52:12.170344
436	7	advisor	comment	Ms. Sammy added comment to upload: Chapter 4	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 08:53:05.79215
437	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 08:53:09.928389
438	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 08:53:26.193055
439	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 08:53:45.672897
440	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 08:53:59.776872
441	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 09:09:46.348352
442	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 09:10:00.250037
443	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 09:23:56.496578
444	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 09:24:10.898505
445	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 09:24:25.170029
446	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 09:24:40.0818
447	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 09:25:09.492494
448	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 09:25:21.803343
449	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 09:31:34.377889
450	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-04 09:32:49.619314
451	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-04 09:32:58.981542
452	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 09:41:01.215245
453	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:35:09.889299
454	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:36:14.157507
455	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:36:30.460644
456	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:40:35.322499
457	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:41:21.057983
458	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:45:00.247348
459	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:45:58.962102
460	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:48:06.448594
461	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:48:47.386825
462	3	advisor	approve	Mr. Acord approved upload: Chapter 3 (Research.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:49:46.881396
463	3	advisor	approve	Mr. Acord approved upload: Chapter 4 (Resume.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:50:07.294617
464	3	advisor	reject	Mr. Acord rejected upload: Chapter 4 (Resume.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:50:19.038298
465	3	advisor	reject	Mr. Acord rejected upload: Chapter 1 (Capstone-Template-DIT.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:50:30.778946
466	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:50:38.977454
467	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:50:55.437209
468	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36	2026-02-04 16:52:12.138429
469	3	advisor	approve	Mr. Acord approved upload: Chapter 1 (Capstone-Template-DIT.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36	2026-02-04 16:52:33.22912
470	3	advisor	approve	Mr. Acord approved upload: Chapter 4 (Resume.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36	2026-02-04 16:53:22.047119
471	2	student	upload	Angeli May April uploaded Chapter 5 (Resume.pdf)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:53:51.832504
472	2	student	upload	Angeli May April uploaded Chapter 5 (Lesson #.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-04 16:54:36.409057
473	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36	2026-02-04 17:04:57.452362
474	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-05 09:40:27.492809
475	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-05 09:40:50.717754
476	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-05 09:42:57.365125
477	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-05 10:18:32.958427
478	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-05 10:18:49.924686
479	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-05 10:18:52.494788
480	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-05 10:19:08.008706
481	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-05 14:45:54.926868
482	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 12:55:06.971749
483	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-06 12:56:40.272829
484	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-06 12:56:53.558535
485	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:00:11.171222
486	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:12:30.108506
487	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:14:06.623638
488	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:14:27.853878
489	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:15:28.896242
490	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:18:00.777545
491	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:20:13.633137
492	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:20:24.233894
493	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:31:56.945769
494	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:39:43.678496
495	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-06 13:46:21.452962
496	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-06 14:07:32.587568
497	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-06 14:09:13.870556
498	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-09 08:30:36.869103
499	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-09 08:57:02.997203
500	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-09 08:57:20.522519
501	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-09 09:19:23.08022
502	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-09 10:50:57.429086
503	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:16:30.748847
504	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:18:48.99163
505	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:19:04.63675
506	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:19:07.950776
507	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:19:24.926808
508	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:28:23.293632
509	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:28:40.170485
510	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:36:12.668095
511	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:40:53.396128
512	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:45:44.448769
513	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:46:00.566345
514	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:53:05.036475
515	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 20:56:20.921367
516	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-10 21:16:38.916849
517	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 21:28:32.509815
518	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-10 21:28:45.843981
519	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-10 21:30:52.210147
520	2	coordinator	login	Sir. Camino logged in	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 04:43:43.770786
521	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:00:38.124943
522	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:14:17.315282
523	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:14:28.760559
524	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:32:04.887298
525	1	admin	delete	Admin deleted backup: backup3.sql	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:32:44.929269
526	1	admin	delete	Admin deleted backup: backup2.sql	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:32:52.671152
527	1	admin	delete	Admin deleted backup: backup1.sql	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:32:58.476873
528	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:37:48.316027
529	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:38:15.010254
530	2	student	logout	Angeli May April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:38:57.841251
531	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:41:06.068611
532	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:41:38.836024
533	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:50:18.864723
534	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:50:38.498633
535	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 05:53:21.388895
536	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 06:02:25.11854
537	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 06:02:36.886956
538	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 06:03:09.184532
539	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 06:03:26.446677
540	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 06:14:02.989852
541	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 06:59:45.051799
542	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 06:59:59.417467
543	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 08:39:58.89304
544	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 08:40:55.672113
545	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 08:42:54.427624
546	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 08:43:45.861
547	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 08:45:03.916687
548	11	student	login	Dexell Jusi logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 08:45:17.999523
549	11	student	logout	Dexell Jusi logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 08:46:22.879719
550	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 09:22:05.807381
551	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 09:22:37.847634
552	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 09:26:38.361829
553	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 09:32:17.204732
554	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 09:32:50.266733
555	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 09:34:39.263375
556	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 13:47:24.699852
557	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 13:51:31.364695
558	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 13:51:49.385048
559	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 13:55:45.34898
560	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:15:48.467352
561	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:16:26.164916
562	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:19:33.839929
563	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:20:01.013361
564	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:27:41.56118
565	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:28:38.289045
566	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:28:53.868836
567	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:29:08.419983
568	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:29:30.920204
569	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:29:42.992325
570	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:37:09.386185
571	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 14:38:07.450002
572	4	student	session_timeout	Michael L. Nadal session expired	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 15:06:07.437858
573	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 15:10:25.944343
574	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 15:10:55.441113
575	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 15:15:38.544328
576	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 15:15:52.087685
577	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 15:16:20.993797
578	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 21:13:59.999117
579	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 21:17:06.452453
580	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 21:17:27.82397
581	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 21:27:22.234864
582	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 21:27:43.097457
583	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 21:58:42.06546
584	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-11 21:59:02.768125
585	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 09:45:15.780133
586	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 10:21:55.686083
587	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 10:22:13.211955
588	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 10:29:21.485683
589	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 10:29:33.977769
590	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 10:35:56.438247
591	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 10:36:24.276932
592	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 14:05:23.742107
593	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 14:34:09.803783
594	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 14:34:51.04323
595	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 20:18:05.094866
596	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 20:21:57.943691
597	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 20:22:16.230641
598	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 20:23:46.614178
599	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-12 20:24:03.600556
600	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 09:33:05.686823
601	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 09:37:26.203902
602	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 09:37:44.023834
603	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 09:51:45.370275
604	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 09:53:18.360804
605	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-13 10:03:33.45062
606	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 14:10:54.412738
607	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 14:34:56.885928
608	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 14:35:22.849805
609	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 15:07:53.607773
610	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 15:08:21.853296
611	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 15:09:01.059999
612	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 15:09:13.455294
613	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-13 15:23:24.631987
614	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/144.0.0.0	2026-02-13 15:23:37.789738
615	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 15:25:13.289917
616	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 15:25:31.062633
617	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 15:38:30.089577
618	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 15:38:46.268301
619	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-13 15:43:19.195606
620	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 15:33:40.796397
621	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-14 15:34:42.838599
622	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-14 15:52:50.90602
623	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 16:08:11.468549
624	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 16:31:13.107473
625	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-14 16:44:36.886167
626	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 16:55:52.952761
627	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 16:56:17.071066
628	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 17:28:20.156112
629	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 17:28:33.784487
630	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-14 18:02:30.593693
631	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 19:32:41.528664
632	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 19:34:51.284771
633	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 20:14:30.499377
634	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 20:14:42.557058
635	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 20:32:51.187885
636	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-14 20:47:51.887443
637	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-14 20:48:19.340421
638	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-14 20:48:38.335588
639	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-14 20:49:08.741737
640	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-14 20:49:35.280123
641	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 20:55:30.015943
642	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 20:55:41.941195
643	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 21:11:18.005379
644	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 21:11:41.164632
645	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 21:12:10.153815
646	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-14 21:12:26.126848
647	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-14 21:12:49.02391
648	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-14 21:14:19.424746
649	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-15 16:10:07.926216
650	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-15 16:35:08.989274
651	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-15 16:35:24.096395
652	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-15 16:57:11.444602
653	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-15 16:57:56.042031
654	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-15 16:58:15.657509
655	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-15 16:58:27.357155
656	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-15 17:01:29.821204
657	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-15 17:01:53.047085
658	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0	2026-02-15 17:02:09.319149
659	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-16 10:39:45.259355
660	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-16 11:05:43.127886
661	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-16 11:07:59.898815
662	3	advisor	session_timeout	Mr. Acord session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-16 13:48:25.282704
663	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-16 13:48:40.874049
664	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-18 18:43:01.66746
665	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-18 18:43:15.495708
666	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-18 19:10:13.968957
667	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-18 19:15:24.840974
668	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-18 19:39:14.369123
669	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-18 19:44:12.025971
670	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 12:05:45.846069
671	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 12:06:03.177722
672	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 12:13:11.875888
673	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 12:13:22.442765
674	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 12:19:26.953426
675	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 12:19:48.065782
676	4	student	session_timeout	Michael L. Nadal session expired	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 12:49:55.36245
677	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 12:50:15.763029
678	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 12:59:42.254389
679	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 12:59:52.527351
680	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 13:10:46.211543
681	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 13:10:56.831094
682	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 13:12:52.826969
683	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 13:13:04.507325
684	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 13:13:12.049437
685	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 13:13:27.032071
686	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 13:13:33.717766
687	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 13:13:43.799512
688	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 13:13:51.004737
689	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 16:49:12.661982
690	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-20 16:49:27.833917
691	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-21 09:43:14.398513
692	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-21 09:44:31.968407
693	1	admin	notification	Admin sent notification to all: Nadal	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-21 09:46:55.023947
694	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-21 09:47:09.108797
695	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-21 09:47:11.898993
696	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-21 09:47:31.115722
697	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-21 09:48:35.299299
698	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-21 09:48:48.346314
699	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-21 09:52:38.375091
700	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:09:39.893042
701	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:20:55.224884
702	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:21:06.708269
703	2	coordinator	notification_read	Sir. Camino marked notification as read: Nadal	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:21:13.075668
704	2	coordinator	notification_deleted	Sir. Camino deleted notification: Nadal	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:21:17.375421
705	2	coordinator	notification_deleted	Sir. Camino deleted notification: new update	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:21:20.331348
706	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:21:25.966563
707	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:21:39.088543
708	7	advisor	notification_read	Ms. Sammy marked notification as read: Meeting	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:29:21.060249
709	7	advisor	notification_deleted	Ms. Sammy deleted notification: Meeting	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:33:46.730198
710	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:33:51.613025
711	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:34:13.741062
712	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:34:28.010275
713	1	admin	notification	Admin sent notification to all: New Update	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:40:16.338415
714	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:40:20.957307
715	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:40:28.893208
716	2	coordinator	notification_deleted	Sir. Camino deleted notification: New Update	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:40:41.030549
717	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 09:57:24.950338
718	1	admin	notification	Admin sent notification to specific: Hello	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 11:15:21.240048
719	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 11:15:24.947617
720	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 11:15:33.188296
721	2	coordinator	notification_read	Sir. Camino marked notification as read: Hello	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 11:15:41.043734
722	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 11:16:54.82899
723	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 11:17:13.352064
724	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-22 11:17:27.351693
725	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 09:19:24.057372
726	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 09:19:41.131632
727	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 09:38:53.041729
728	2	student	login	Angeli May April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 09:40:42.512997
729	2	student	logout	Angeli Mae April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 09:45:23.710087
730	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 09:54:16.338683
731	11	student	login	Dexell Jusi logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 09:54:46.298912
732	11	student	logout	Dexell O Jusi logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 09:56:46.918136
733	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 09:58:54.246628
734	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:00:13.077905
735	8	student	login	Larraine Natalia logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:00:33.789637
736	8	student	logout	Larraine Natalia B Camposano logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:01:58.116155
737	6	student	login	Jayson Cute Santollanii logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:02:30.553006
738	6	student	logout	Jayson Pangit Santollanii logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:03:25.698114
739	9	student	login	Patrick Etenac logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:03:52.369494
740	9	student	logout	Patrick R. Etenac logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:04:53.866834
741	10	student	login	Leornard Adique logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:05:28.237265
742	10	student	logout	Leonard C Adik-que logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:06:56.431026
743	12	student	login	Juan Dela Cruz logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:07:10.733758
744	12	student	logout	Juan Dela Cruz logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:07:56.286189
745	13	student	login	Jane Doe logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:08:34.135403
746	13	student	logout	Jane Z Doe logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:09:26.36652
747	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:11:20.353509
748	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:30:26.621266
749	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:37:04.447276
750	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:37:28.770004
751	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:39:23.014426
752	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:39:38.416106
753	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 10:46:38.666202
754	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 11:10:43.68971
755	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-23 11:11:03.436381
756	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 08:02:20.310372
757	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 08:16:25.393823
758	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 08:16:43.551099
759	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 08:18:27.972748
760	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 08:18:46.519872
761	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 08:38:57.241597
762	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 08:39:06.77675
763	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 08:51:31.407975
764	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 08:51:40.550093
765	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 09:02:26.147255
766	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 09:02:35.756592
767	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 11:06:31.09031
768	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 11:06:51.452495
769	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 11:19:34.460327
770	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 11:19:47.454358
771	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 11:26:22.530949
772	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 11:26:35.345033
773	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 11:45:51.890135
774	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 12:43:02.695366
775	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 12:43:28.215557
776	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:03:48.061559
777	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:03:57.014961
778	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:40:18.381582
779	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:40:34.442853
780	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:44:04.630405
781	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:46:29.684546
782	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:48:21.337354
783	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:48:36.536228
784	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:53:27.102144
785	2	student	login	Angeli Mae April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:54:30.736339
786	2	student	logout	Angeli Mae April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:55:59.903148
787	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:56:20.384395
788	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:56:31.54223
789	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:56:44.033537
790	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:58:24.724583
791	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 13:59:55.542186
792	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 14:20:01.916485
793	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 14:20:34.750324
794	2	coordinator	notification_deleted	Sir. Camino deleted notification: Hello	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 14:21:40.321441
795	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/145.0.0.0	2026-02-24 14:32:18.485878
796	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 15:06:03.758956
797	4	student	session_timeout	Michael L. Nadal session expired	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 15:38:58.724437
798	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 15:39:23.549241
799	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 15:54:07.377336
800	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 16:16:56.750298
801	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 16:29:27.319997
802	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 16:29:38.048559
803	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 16:33:50.868756
804	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 16:34:06.068176
805	4	student	session_timeout	Michael L. Nadal session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 16:54:39.791965
806	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 16:54:55.84018
807	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:03:49.661375
808	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:04:05.84663
809	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:04:33.52952
810	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:04:58.618909
811	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:25:14.066223
812	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:25:34.966914
813	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:41:43.873844
814	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:45:58.638816
815	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:46:56.845362
816	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:47:09.418408
817	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:49:34.601945
818	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:49:49.386883
819	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:50:03.788557
820	2	student	login	Angeli Mae April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 17:50:21.994404
821	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 18:05:22.876369
822	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 19:21:17.973049
823	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 19:21:45.99284
824	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 19:22:01.072465
825	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 19:23:02.408543
826	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 19:23:13.948712
827	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 19:31:19.796463
828	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 19:31:31.766773
829	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 19:36:51.344671
830	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 19:37:00.10831
831	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 20:00:01.520169
832	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 20:00:20.579965
833	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 20:15:18.33532
834	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 20:15:37.174576
835	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 20:16:41.308069
836	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 20:16:55.125046
837	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:14:30.874271
838	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:15:42.105087
839	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-24 21:16:04.709944
840	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:16:08.157987
841	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:16:20.391297
842	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-24 21:17:17.675674
843	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:17:21.470344
844	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:18:01.319636
845	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:21:06.162717
846	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:21:20.095675
847	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:22:00.59816
848	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-24 21:22:19.352011
849	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:22:26.608319
850	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:22:40.314758
851	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:25:09.716707
852	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:25:23.585548
853	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:32:45.337485
854	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:32:57.433671
855	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:34:00.399261
856	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:34:17.489974
857	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:35:22.802617
858	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:37:17.92934
859	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:39:22.666411
860	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-24 21:39:51.104302
861	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-24 21:41:37.799378
862	8	advisor	login	Ms. Samantha logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 06:07:46.836813
863	8	advisor	session_timeout	Ms. Samantha session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 06:22:52.742957
864	8	advisor	login	Ms. Samantha logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 06:23:15.695334
865	8	advisor	login	Ms. Samantha logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 08:36:20.630697
866	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-25 08:40:07.504006
867	8	advisor	logout	Ms. Samantha logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 08:40:43.804362
868	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 08:41:04.264611
869	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-25 08:50:38.583125
870	4	student	session_timeout	Michael L. Nadal session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 08:51:35.363632
871	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 08:52:09.845977
872	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 09:02:21.728468
873	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 09:02:31.626606
874	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 09:09:51.175268
875	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 09:10:03.555758
876	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:26:50.822815
877	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:27:05.685482
878	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:29:11.306603
879	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:29:21.93696
880	7	advisor	session_timeout	Ms. Sammy session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:41:47.25223
881	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:42:06.431685
882	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:42:08.823966
883	8	advisor	login	Ms. Samantha logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:42:24.517738
884	8	advisor	logout	Ms. Samantha logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:42:44.289226
885	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:42:58.463408
886	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:43:38.682772
887	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:44:17.519041
888	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:49:11.514321
889	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:49:55.069803
890	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:50:36.205435
891	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:51:03.203889
892	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:52:44.79967
893	8	advisor	login	Ms. Samantha logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:52:58.394138
894	8	advisor	logout	Ms. Samantha logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:53:22.710006
895	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:53:37.939906
896	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:57:01.697135
897	8	advisor	login	Ms. Samantha logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:57:19.964262
898	8	advisor	logout	Ms. Samantha logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:57:29.748125
899	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:57:44.128247
900	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:57:50.710252
901	8	advisor	login	Ms. Samantha logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 10:58:36.948792
902	8	advisor	logout	Ms. Samantha logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 11:02:18.085056
903	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 11:02:52.582412
904	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 11:11:20.870012
905	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 11:38:49.649142
906	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 11:40:41.388485
907	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 11:42:22.676667
908	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 11:42:33.82017
909	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 11:43:09.550654
910	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 11:43:17.691907
911	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 12:57:03.583853
912	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 12:57:25.29116
913	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 12:58:29.180855
914	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 12:58:44.340013
915	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 13:22:28.545884
916	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 15:10:00.281061
917	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 16:19:34.03605
918	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 16:19:46.092036
919	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 16:30:09.525859
920	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 16:36:45.880735
921	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 16:37:10.70624
922	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 16:37:24.202965
923	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-25 16:39:04.843302
924	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-25 16:54:55.270089
925	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-25 16:54:56.576332
926	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 09:22:53.788506
927	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-26 09:25:01.699044
928	4	student	session_timeout	Michael L. Nadal session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 09:46:33.150413
929	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 09:46:46.452904
930	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-02-26 09:57:31.279977
931	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 10:03:01.62864
932	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 10:13:28.252629
933	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 13:55:11.870743
934	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 14:10:41.523523
935	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 14:10:54.175418
936	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 14:11:56.886411
937	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 14:12:18.174528
938	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 14:13:14.101982
939	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 14:13:23.945322
940	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 14:13:35.035941
941	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-26 14:13:48.435405
942	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-27 10:15:00.747766
943	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-27 10:18:15.390849
944	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-27 11:08:13.419732
945	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-27 11:08:19.360543
946	1	admin	backup	Admin created database backup: backup_2026-02-27.sql (0.24 MB)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-27 11:09:40.161543
947	1	admin	settings_update	Updated system settings: backup_frequency, max_upload_size, session_timeout, site_name, system_name, maintenance_mode	::1	\N	2026-02-27 11:34:43.886804
948	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-28 14:20:52.378036
949	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-28 14:35:41.853684
950	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-28 14:35:59.031541
951	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-28 14:36:16.338162
952	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-28 15:14:01.296514
953	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-02-28 15:14:11.826233
954	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-01 08:25:13.398668
955	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-01 08:40:19.283574
956	7	advisor	logout	Ms. Sammy logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-01 08:59:24.819866
957	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-01 09:26:38.638349
958	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-01 09:26:51.467445
959	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-01 09:39:17.756748
960	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-01 09:49:39.799091
961	3	advisor	login	Mr. Acord logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-01 09:54:15.867263
962	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-01 10:26:45.707321
963	3	advisor	logout	Mr. Acord logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-01 10:26:48.08106
964	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-02 13:30:00.318655
965	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-02 13:40:28.541997
966	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-02 13:40:49.590849
967	2	coordinator	session_timeout	Sir. Camino session expired due to inactivity	::1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/145.0.0.0	2026-03-02 14:08:41.420517
968	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-02 14:46:28.252871
969	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-02 14:50:22.250709
970	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-02 14:50:40.74171
971	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-02 14:51:22.304653
972	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-02 14:51:34.9695
973	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-02 15:00:03.452639
974	2	coordinator	login	Sir. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-02 15:39:50.130055
975	7	advisor	login	Ms. Sammy logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-02 15:40:23.872537
976	2	coordinator	logout	Sir. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-02 16:04:55.241393
977	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-02 16:05:47.843009
978	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-02 16:22:57.623013
979	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-03 16:15:43.091514
980	2	coordinator	login	Honesto O. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-03 16:15:53.436789
981	2	coordinator	logout	Honesto O. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-03 16:17:02.856809
982	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-03 16:17:30.241557
983	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-03 16:18:42.950952
984	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 08:13:09.714976
985	2	coordinator	login	Honesto O. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 08:15:08.236697
986	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 08:27:33.825476
987	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 08:27:45.800535
988	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 08:32:14.606454
989	3	advisor	login	Teacher A logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 08:32:24.329436
990	3	advisor	logout	Teacher A logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 08:40:31.659049
991	7	advisor	login	Teacher B logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 08:40:40.911598
992	2	coordinator	logout	Honesto O. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 08:43:23.908371
993	2	coordinator	login	Honesto O. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 08:45:42.475582
994	2	coordinator	logout	Honesto O. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 08:46:54.578456
995	7	advisor	logout	Teacher B logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 08:48:47.609997
996	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 08:49:07.060371
997	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 15:42:02.216489
998	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 15:53:01.5565
999	3	advisor	login	Teacher A logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 15:53:13.187204
1000	3	advisor	logout	Teacher A logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 15:53:15.957583
1001	7	advisor	login	Teacher B logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 15:53:24.04921
1002	7	advisor	reject	Teacher B rejected upload: Chapter 4 (Use Case Diagram.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 15:53:40.921914
1003	7	advisor	logout	Teacher B logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 15:58:52.746342
1004	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:00:37.856337
1005	2	student	login	Angeli Mae April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:00:51.714071
1006	1	admin	session_timeout	Admin session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 16:17:31.330295
1007	2	student	logout	Angeli Mae April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:25:30.308487
1008	3	advisor	login	Teacher A logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:25:48.100228
1009	3	advisor	approve	Teacher A approved upload: Chapter 5 (Lesson #.docx)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:26:00.466898
1010	3	advisor	logout	Teacher A logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:26:21.19331
1011	2	student	login	Angeli Mae April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:26:32.874548
1012	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 16:27:06.130854
1013	2	coordinator	login	Honesto O. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 16:27:20.940601
1014	2	coordinator	logout	Honesto O. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 16:27:42.312184
1015	2	student	login	Angeli Mae April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 16:27:53.517557
1016	2	student	logout	Angeli Mae April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:28:34.061851
1017	2	coordinator	login	Honesto O. Camino logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:28:53.829443
1018	2	coordinator	logout	Honesto O. Camino logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:29:28.081161
1019	2	student	logout	Angeli Mae April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 16:30:19.38721
1020	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 16:30:28.650867
1021	4	student	upload	Michael L. Nadal uploaded Chapter 4 (Module 1.pdf)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 16:34:40.667571
1022	4	student	upload	Michael L. Nadal uploaded Chapter 4 (Module 1.pdf)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 16:37:26.523761
1023	1	admin	session_timeout	Admin session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:51:13.031781
1024	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:52:43.264235
1025	7	advisor	login	Teacher B logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:53:00.054941
1026	7	advisor	approve	Teacher B approved upload: Chapter 4 (Module 1.pdf)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 16:53:13.325792
1027	7	advisor	logout	Teacher B logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 17:03:20.900767
1028	3	advisor	login	Teacher A logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 17:07:09.253563
1029	3	advisor	logout	Teacher A logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-04 17:17:29.845524
1030	4	student	session_timeout	Michael L. Nadal session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-04 17:49:24.419927
1031	7	advisor	login	Teacher B logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-05 08:59:11.578684
1032	1	admin	logout	Admin logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-05 09:06:29.179903
1033	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-05 09:06:52.11126
1034	4	student	upload	Michael L. Nadal uploaded Chapter 5 (Application_for_graduation (1).pdf)	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-05 09:09:40.632277
1035	4	student	logout	Michael L. Nadal logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36	2026-03-05 09:25:06.17962
1036	7	advisor	session_timeout	Teacher B session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-05 09:48:13.962855
1037	2	student	login	Angeli Mae April logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-05 09:48:30.102911
1038	2	student	logout	Angeli Mae April logged out	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-05 09:48:34.556686
1039	4	student	login	Michael L. Nadal logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-05 09:48:43.985091
1040	7	advisor	login	Teacher B logged in	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-05 17:11:06.322152
1041	7	advisor	session_timeout	Teacher B session expired due to inactivity	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0	2026-03-05 17:44:37.51747
\.


--
-- Data for Name: system_notifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.system_notifications (id, notification_type, recipient_type, recipient_id, title, message, priority, status, created_by, sent_at, created_at) FROM stdin;
3	system	all	\N	Nadal	HI!! Tapusin nyo na lahat	normal	deleted	1	2026-02-21 09:46:55.015758	2026-02-21 09:46:55.015758
1	system	all	\N	new update	new update will be heree	normal	deleted	1	2025-12-25 14:10:51.555653	2025-12-25 14:10:51.555653
2	system	advisors	\N	Meeting	there is an urgent meeting	urgent	deleted	1	2025-12-30 11:42:37.780978	2025-12-30 11:42:37.780978
4	system	all	\N	New Update	There will be a new update	urgent	deleted	1	2026-02-22 09:40:16.335644	2026-02-22 09:40:16.335644
5	system	specific	2	Hello	Hello test test	normal	deleted	1	2026-02-22 11:15:21.2325	2026-02-22 11:15:21.2325
\.


--
-- Data for Name: system_settings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.system_settings (id, setting_key, setting_value, setting_type, description, updated_by, updated_at, created_at) FROM stdin;
5	backup_frequency	weekly	text	Automatic backup frequency (daily/weekly/monthly)	1	2026-02-27 11:34:43.855344	2025-12-24 16:12:02.037945
2	max_upload_size	15	number	Maximum file upload size in MB	1	2026-02-27 11:34:43.855344	2025-12-24 16:12:02.037945
3	session_timeout	10	number	Session timeout in minutes	1	2026-02-27 11:34:43.855344	2025-12-24 16:12:02.037945
1	site_name	Research Monitoring Systems	text	System name displayed across the application	1	2026-02-27 11:34:43.855344	2025-12-24 16:12:02.037945
9	system_name	Research Monitoring System	text	System name for emails	1	2026-02-27 11:34:43.855344	2025-12-30 07:47:50.317521
6	maintenance_mode	false	boolean	Enable maintenance mode	1	2026-02-27 11:34:43.855344	2025-12-24 16:12:02.037945
\.


--
-- Data for Name: un_sdgs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.un_sdgs (id, name, description, created_at, advisor_id) FROM stdin;
3	Gender Equality	\N	2025-12-30 12:57:22.986203	\N
4	Reduced Inequalities	\N	2025-12-30 12:57:41.918401	\N
17	No Poverty	\N	2026-03-02 13:45:24.534591	\N
18	Zero Hunger	\N	2026-03-02 13:45:33.935164	\N
19	Good Health and Well-being	\N	2026-03-02 13:45:43.802171	\N
20	Quality Education	\N	2026-03-02 13:51:01.964811	\N
21	Clean Water and Sanitation	\N	2026-03-02 13:58:35.774122	\N
22	Affordable and Clean Energy	\N	2026-03-02 13:58:44.050866	\N
23	Decent Work and Economic Growth	\N	2026-03-02 13:59:52.374629	\N
24	Industry, Innovation and Infrastructure	\N	2026-03-02 14:00:07.359931	\N
25	Sustainable Cities and Communities	\N	2026-03-02 14:00:28.562913	\N
26	Responsible Consumption and Production	\N	2026-03-02 14:00:38.191816	\N
27	Climate Action	\N	2026-03-02 14:00:50.413681	\N
28	Life Below Water	\N	2026-03-02 14:01:02.529949	\N
29	Life on Land	\N	2026-03-02 14:01:13.042945	\N
30	Peace, Justice and Strong Institutions	\N	2026-03-02 14:01:22.866003	\N
31	Partnerships for the Goals	\N	2026-03-02 14:01:35.496965	\N
\.


--
-- Data for Name: uploads; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.uploads (upload_id, school_id, task_name, file_path, original_filename, uploaded_at, status, comment, base_grade, final_grade, submission_timing) FROM stdin;
1	2023-00178-UQ-0	Chapter 1	uploads/FILE_692112398d1706.11635742.docx	6 PUP Consent Form.docx	2025-11-22 09:30:33.580942	pending	\N	\N	\N	\N
2	2023-00178-UQ-0	Chapter 1	uploads/FILE_6926505895caa1.45358366.docx	is a systematic.docx	2025-11-26 08:56:56.626673	pending	\N	\N	\N	\N
4	2023-00178-UQ-0	Chapter 1	uploads/FILE_693f547d2a0404.04137293.docx	Resume.docx	2025-12-15 08:21:17.184335	pending	\N	\N	\N	\N
21	2023-00179-UQ-0	Chapter 3	../uploads/FILE_697b1422218a69.95658889.docx	Research.docx	2026-01-29 16:02:42.13849	approved	Basta	\N	\N	\N
7	2023-00178-UQ-0	Chapter 2	uploads/FILE_6947a939d33617.98616598.docx	is a systematic.docx	2025-12-21 16:00:57.86774	rejected	Wrong file	\N	\N	\N
6	2023-00178-UQ-0	Chapter 1	uploads/FILE_694221e9cf33a4.29123121.docx	themeandcitations.docx	2025-12-17 11:22:17.854461	approved	there is nothing wrong here	\N	\N	\N
9	2023-00179-UQ-0	Chapter 2	uploads/FILE_6949e7a41558b4.15833730.docx	is a systematic.docx	2025-12-23 08:51:48.092832	pending	\N	\N	\N	\N
8	2023-00179-UQ-0	Chapter 1	uploads/FILE_694870b8017728.64274501.docx	Capstone-Template-DIT.docx	2025-12-22 06:12:08.01154	approved	I approved this, but need to polish the DOT	\N	\N	\N
10	2023-00179-UQ-0	Chapter 2	uploads/FILE_6949e7b41b9e45.19649550.docx	6 PUP Consent Form.docx	2025-12-23 08:52:04.120885	approved	The spacing needed to be fixed, but approved	\N	\N	\N
11	2023-00178-UQ-0	Chapter 2	uploads/FILE_694cca472cd9e8.42554366.docx	InternsEvaluation.docx	2025-12-25 13:23:19.186892	approved	\N	\N	\N	\N
12	2023-00179-UQ-0	Chapter 3	uploads/FILE_694cd17ecba8b8.69531651.docx	DOCUMENT-SUBMISSION-TEMPLATE_292760372.docx	2025-12-25 13:54:06.83677	rejected	\N	\N	\N	\N
22	2023-00179-UQ-0	Chapter 4	../uploads/FILE_697eee64e333d7.52809296.docx	Resume.docx	2026-02-01 14:10:44.936226	approved	\N	\N	\N	\N
13	2023-00178-UQ-0	Chapter 3	uploads/FILE_694f429aaf0c96.68260559.docx	Chapter_2_Integration_of_Data_Analytics.docx	2025-12-27 10:21:14.718213	rejected	❌ Overall Quality: Needs significant revision.\r\n\r\n📝 Expand content. Current: 1671 words, Recommended: 2500+ words.\r\n\r\n📋 Add missing sections: Introduction, Methodology, References\r\n\r\n✏️ Grammar: 1 potential issues found. Review document carefully.\r\n\r\n🏗️ Structure: Word count (1671) is below recommended minimum (2500 words) Missing required sections: Research design, Methodology, Participants, Instruments, Data collection, Data analysis\r\n\r\n📚 Content: No citations detected. Research papers should include proper citations. No references section found. All research papers must include references. First-person pronouns detected. Consider using third-person perspective in academic writing.	\N	\N	\N
14	2023-00178-UQ-0	Chapter 3	uploads/FILE_694fb8b1548c74.02078147.docx	is a systematic.docx	2025-12-27 18:45:05.351285	pending	\N	\N	\N	\N
15	2023-00178-UQ-0	Chapter 3	uploads/FILE_69507d16cc8ac7.84837765.docx	is a systematic.docx	2025-12-28 08:43:02.84287	pending	\N	\N	\N	\N
23	2023-00179-UQ-0	Chapter 5	../uploads/FILE_6983091fca3694.50898952.pdf	Resume.pdf	2026-02-04 16:53:51.830561	pending	\N	\N	\N	\N
16	2023-00178-UQ-0	Chapter 3	uploads/FILE_6951cc4bbda766.56733541.pdf	ethical considerations.pdf	2025-12-29 08:33:15.780547	approved	\N	\N	\N	\N
18	2023-00178-UQ-0	Chapter 4	uploads/FILE_695dd904b4d142.86096623.docx	Research Monitoring System.docx	2026-01-07 11:54:44.745439	rejected	Improve your spacing....	\N	\N	\N
17	2023-00158-UQ-0	Chapter 1	uploads/FILE_69566c034aa227.69468857.docx	InternsEvaluation.docx	2026-01-01 20:43:47.309348	approved	goods	\N	\N	\N
20	2023-00179-UQ-0	Chapter 3	../uploads/FILE_697b1405e8c264.21494464.docx	is a systematic.docx	2026-01-29 16:02:13.956881	pending	\N	\N	\N	\N
19	2023-00178-UQ-0	Chapter 4	../uploads/FILE_696563f93d8fa9.13181581.docx	Use Case Diagram.docx	2026-01-13 05:13:29.25854	rejected	may mali sa chapter 4 nyo	\N	\N	\N
24	2023-00179-UQ-0	Chapter 5	../uploads/FILE_6983094c62bd86.34851486.docx	Lesson #.docx	2026-02-04 16:54:36.40651	approved	\N	\N	\N	\N
25	2023-00178-UQ-0	Chapter 4	../uploads/FILE_69a7eea0a1da16.62750006.pdf	Module 1.pdf	2026-03-04 16:34:40.664579	pending	\N	\N	\N	\N
26	2023-00178-UQ-0	Chapter 4	../uploads/FILE_69a7ef467ec440.08387070.pdf	Module 1.pdf	2026-03-04 16:37:26.521152	approved	\N	\N	\N	\N
27	2023-00178-UQ-0	Chapter 5	../uploads/FILE_69a8d7d49952e6.75704909.pdf	Application_for_graduation (1).pdf	2026-03-05 09:09:40.629626	approved		90.00	95.00	early
\.


--
-- Data for Name: urec_documents; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.urec_documents (id, group_id, school_id, document_type, file_path, original_filename, status, comment, uploaded_at, updated_at, adviser_id) FROM stdin;
2	233	2023-00178-UQ-0	UREC Clearance	uploads/694b8f4997147_1766559561.pdf	ssrn-4101636.pdf	approved	\N	2025-12-24 14:59:21.620528	2025-12-24 14:59:21.620528	\N
1	233	2023-00178-UQ-0	UREC Form	uploads/694b8f3b45870_1766559547.pdf	POLYTECHNIC UNIVERSITY OF THE PHILIPPINES.pdf	approved	goods ig	2025-12-24 14:59:07.288318	2025-12-24 14:59:07.288318	\N
13	233	\N	UREC Form	uploads/697590c821416_1769312456.png	Screenshot 2024-04-20 145154.png	approved	\N	2026-01-25 11:40:56.143018	2026-01-25 11:40:56.143018	7
14	223	\N	UREC Form	uploads/69759233bdfb1_1769312819.docx	Letterssss.docx	approved	\N	2026-01-25 11:46:59.779619	2026-01-25 11:46:59.779619	3
15	223	\N	UREC Clearance	uploads/697592453e9e5_1769312837.docx	is a systematic.docx	approved	\N	2026-01-25 11:47:17.258006	2026-01-25 11:47:17.258006	3
16	223	\N	UREC Form	uploads/697b146225274_1769673826.docx	SDLC.docx	approved	\N	2026-01-29 16:03:46.153099	2026-01-29 16:03:46.153099	3
17	223	\N	UREC Form	uploads/697ef9e74788c_1769929191.docx	Lesson #.docx	approved	\N	2026-02-01 14:59:51.29482	2026-02-01 14:59:51.29482	3
18	223	\N	UREC Form	uploads/697efbda4bc73_1769929690.docx	Nadal_UIUXActivity.docx	approved	\N	2026-02-01 15:08:10.312158	2026-02-01 15:08:10.312158	3
19	223	\N	UREC Form	uploads/6983fb96a346b_1770257302.pdf	Resume.pdf	approved	\N	2026-02-05 10:08:22.675316	2026-02-05 10:08:22.675316	3
\.


--
-- Name: academic_years_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.academic_years_id_seq', 1, true);


--
-- Name: admin_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.admin_id_seq', 1, true);


--
-- Name: advisor_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.advisor_id_seq', 9, true);


--
-- Name: chapter_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.chapter_settings_id_seq', 3, true);


--
-- Name: coordinator_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.coordinator_id_seq', 2, true);


--
-- Name: database_backups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.database_backups_id_seq', 6, true);


--
-- Name: error_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.error_logs_id_seq', 1, false);


--
-- Name: group_milestones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.group_milestones_id_seq', 3, true);


--
-- Name: group_sdgs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.group_sdgs_id_seq', 13, true);


--
-- Name: group_thrusts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.group_thrusts_id_seq', 8, true);


--
-- Name: groups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.groups_id_seq', 261, true);


--
-- Name: programs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.programs_id_seq', 3, true);


--
-- Name: progress_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.progress_id_seq', 1, false);


--
-- Name: report_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.report_logs_id_seq', 19, true);


--
-- Name: research_statuses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.research_statuses_id_seq', 6, true);


--
-- Name: research_thrusts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.research_thrusts_id_seq', 26, true);


--
-- Name: student_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.student_id_seq', 17, true);


--
-- Name: system_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.system_logs_id_seq', 1041, true);


--
-- Name: system_notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.system_notifications_id_seq', 5, true);


--
-- Name: system_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.system_settings_id_seq', 9, true);


--
-- Name: un_sdgs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.un_sdgs_id_seq', 31, true);


--
-- Name: uploads_upload_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.uploads_upload_id_seq', 27, true);


--
-- Name: urec_documents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.urec_documents_id_seq', 19, true);


--
-- Name: academic_years academic_years_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.academic_years
    ADD CONSTRAINT academic_years_pkey PRIMARY KEY (id);


--
-- Name: academic_years academic_years_year_start_year_end_semester_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.academic_years
    ADD CONSTRAINT academic_years_year_start_year_end_semester_key UNIQUE (year_start, year_end, semester);


--
-- Name: admin admin_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.admin
    ADD CONSTRAINT admin_pkey PRIMARY KEY (id);


--
-- Name: advisor advisor_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.advisor
    ADD CONSTRAINT advisor_pkey PRIMARY KEY (id);


--
-- Name: chapter_settings chapter_settings_group_id_chapter_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chapter_settings
    ADD CONSTRAINT chapter_settings_group_id_chapter_name_key UNIQUE (group_id, chapter_name);


--
-- Name: chapter_settings chapter_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chapter_settings
    ADD CONSTRAINT chapter_settings_pkey PRIMARY KEY (id);


--
-- Name: coordinator coordinator_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.coordinator
    ADD CONSTRAINT coordinator_pkey PRIMARY KEY (id);


--
-- Name: database_backups database_backups_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.database_backups
    ADD CONSTRAINT database_backups_pkey PRIMARY KEY (id);


--
-- Name: error_logs error_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.error_logs
    ADD CONSTRAINT error_logs_pkey PRIMARY KEY (id);


--
-- Name: group_milestones group_milestones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_milestones
    ADD CONSTRAINT group_milestones_pkey PRIMARY KEY (id);


--
-- Name: group_sdgs group_sdgs_group_id_sdg_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_sdgs
    ADD CONSTRAINT group_sdgs_group_id_sdg_id_key UNIQUE (group_id, sdg_id);


--
-- Name: group_sdgs group_sdgs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_sdgs
    ADD CONSTRAINT group_sdgs_pkey PRIMARY KEY (id);


--
-- Name: group_thrusts group_thrusts_group_id_thrust_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_thrusts
    ADD CONSTRAINT group_thrusts_group_id_thrust_id_key UNIQUE (group_id, thrust_id);


--
-- Name: group_thrusts group_thrusts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_thrusts
    ADD CONSTRAINT group_thrusts_pkey PRIMARY KEY (id);


--
-- Name: groups groups_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_name_key UNIQUE (name);


--
-- Name: groups groups_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (id);


--
-- Name: programs programs_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.programs
    ADD CONSTRAINT programs_code_key UNIQUE (code);


--
-- Name: programs programs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.programs
    ADD CONSTRAINT programs_pkey PRIMARY KEY (id);


--
-- Name: progress progress_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progress
    ADD CONSTRAINT progress_pkey PRIMARY KEY (id);


--
-- Name: progress progress_research_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.progress
    ADD CONSTRAINT progress_research_id_key UNIQUE (research_id);


--
-- Name: report_logs report_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_logs
    ADD CONSTRAINT report_logs_pkey PRIMARY KEY (id);


--
-- Name: research_statuses research_statuses_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.research_statuses
    ADD CONSTRAINT research_statuses_name_key UNIQUE (name);


--
-- Name: research_statuses research_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.research_statuses
    ADD CONSTRAINT research_statuses_pkey PRIMARY KEY (id);


--
-- Name: research_thrusts research_thrusts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.research_thrusts
    ADD CONSTRAINT research_thrusts_pkey PRIMARY KEY (id);


--
-- Name: student student_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_pkey PRIMARY KEY (id);


--
-- Name: student student_school_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_school_id_key UNIQUE (school_id);


--
-- Name: system_logs system_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_logs
    ADD CONSTRAINT system_logs_pkey PRIMARY KEY (id);


--
-- Name: system_notifications system_notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_notifications
    ADD CONSTRAINT system_notifications_pkey PRIMARY KEY (id);


--
-- Name: system_settings system_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_pkey PRIMARY KEY (id);


--
-- Name: system_settings system_settings_setting_key_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_setting_key_key UNIQUE (setting_key);


--
-- Name: un_sdgs un_sdgs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.un_sdgs
    ADD CONSTRAINT un_sdgs_pkey PRIMARY KEY (id);


--
-- Name: group_milestones unique_group; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_milestones
    ADD CONSTRAINT unique_group UNIQUE (group_id);


--
-- Name: uploads uploads_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.uploads
    ADD CONSTRAINT uploads_pkey PRIMARY KEY (upload_id);


--
-- Name: urec_documents urec_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.urec_documents
    ADD CONSTRAINT urec_documents_pkey PRIMARY KEY (id);


--
-- Name: idx_backups_created; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_backups_created ON public.database_backups USING btree (created_at);


--
-- Name: idx_error_logs_created; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_error_logs_created ON public.error_logs USING btree (created_at);


--
-- Name: idx_group_sdgs_group; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_group_sdgs_group ON public.group_sdgs USING btree (group_id);


--
-- Name: idx_group_sdgs_sdg; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_group_sdgs_sdg ON public.group_sdgs USING btree (sdg_id);


--
-- Name: idx_group_thrusts_group; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_group_thrusts_group ON public.group_thrusts USING btree (group_id);


--
-- Name: idx_group_thrusts_thrust; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_group_thrusts_thrust ON public.group_thrusts USING btree (thrust_id);


--
-- Name: idx_groups_pending_titles; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_groups_pending_titles ON public.groups USING btree (title_submitted_at) WHERE ((title_status)::text = 'pending'::text);


--
-- Name: idx_groups_title_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_groups_title_status ON public.groups USING btree (title_status);


--
-- Name: idx_notifications_recipient; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_notifications_recipient ON public.system_notifications USING btree (recipient_type, recipient_id);


--
-- Name: idx_system_logs_created; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_system_logs_created ON public.system_logs USING btree (created_at);


--
-- Name: idx_system_logs_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_system_logs_user ON public.system_logs USING btree (user_id);


--
-- Name: one_leader_per_group; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX one_leader_per_group ON public.student USING btree (group_id) WHERE (is_leader = true);


--
-- Name: chapter_settings chapter_settings_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chapter_settings
    ADD CONSTRAINT chapter_settings_group_id_fkey FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- Name: report_logs fk_coordinator; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_logs
    ADD CONSTRAINT fk_coordinator FOREIGN KEY (generated_by) REFERENCES public.coordinator(id);


--
-- Name: group_milestones fk_group; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_milestones
    ADD CONSTRAINT fk_group FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- Name: groups fk_sdg; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT fk_sdg FOREIGN KEY (sdg_id) REFERENCES public.un_sdgs(id);


--
-- Name: groups fk_thrust; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT fk_thrust FOREIGN KEY (thrust_id) REFERENCES public.research_thrusts(id);


--
-- Name: group_sdgs group_sdgs_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_sdgs
    ADD CONSTRAINT group_sdgs_group_id_fkey FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- Name: group_sdgs group_sdgs_sdg_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_sdgs
    ADD CONSTRAINT group_sdgs_sdg_id_fkey FOREIGN KEY (sdg_id) REFERENCES public.un_sdgs(id) ON DELETE CASCADE;


--
-- Name: group_thrusts group_thrusts_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_thrusts
    ADD CONSTRAINT group_thrusts_group_id_fkey FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- Name: group_thrusts group_thrusts_thrust_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.group_thrusts
    ADD CONSTRAINT group_thrusts_thrust_id_fkey FOREIGN KEY (thrust_id) REFERENCES public.research_thrusts(id) ON DELETE CASCADE;


--
-- Name: groups groups_adviser_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_adviser_id_fkey FOREIGN KEY (adviser_id) REFERENCES public.advisor(id);


--
-- Name: student student_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_group_id_fkey FOREIGN KEY (group_id) REFERENCES public.groups(id);


--
-- Name: urec_documents urec_documents_adviser_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.urec_documents
    ADD CONSTRAINT urec_documents_adviser_id_fkey FOREIGN KEY (adviser_id) REFERENCES public.advisor(id);


--
-- Name: urec_documents urec_documents_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.urec_documents
    ADD CONSTRAINT urec_documents_group_id_fkey FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

