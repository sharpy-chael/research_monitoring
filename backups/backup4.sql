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
    year_start integer NOT NULL,
    year_end integer NOT NULL,
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
    is_active boolean DEFAULT true
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
-- Name: coordinator; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.coordinator (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    pass_word character varying(255) NOT NULL,
    is_active boolean DEFAULT true
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
    thrust_id integer
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
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
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
    is_active boolean DEFAULT true
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
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
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
    comment text
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
    school_id character varying(50) NOT NULL,
    document_type character varying(50) NOT NULL,
    file_path character varying(255) NOT NULL,
    original_filename character varying(255) NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying,
    comment text,
    uploaded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
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
1	2024	2025	2	t	2025-12-24 13:04:26.281292
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

COPY public.advisor (id, name, pass_word, files, images, is_active) FROM stdin;
3	Mr. Acord	$2y$10$7I3Ou47Tb4vPmRMrbfD2I.empyw2QeqotdBQFeUcjeDdBlmppEQh2	\N	1763697101_milkfish1.jpg	t
7	Ms. Sammy	$2y$10$jgu81mI/BglMOITVaHsziujs69oTq/PtazFQbnaAuowGSnw9I18ZG	\N	\N	t
\.


--
-- Data for Name: coordinator; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.coordinator (id, name, pass_word, is_active) FROM stdin;
2	Mr. Oni	$2y$10$tCdF98q14cDK5XAIMh/ENu1yf4WVerbrofqGXmONmNFK1aAtZm1OK	t
1	Mr. B	$2y$10$ihonYC2/Di9vX5BeJWTZ9.GW1Exf614UkByBpI8NdXYOHJI3Ke.Xm	f
\.


--
-- Data for Name: database_backups; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.database_backups (id, backup_name, file_path, file_size, backup_type, created_by, status, notes, created_at) FROM stdin;
1	backup1.sql	../backups/backup1.sql	\N	manual	1	failed	Backup failed: 	2025-12-25 13:11:57.625814
2	backup2.sql	../backups/backup2.sql	\N	manual	1	failed	Backup failed: 	2025-12-25 14:00:15.365813
3	backup3.sql	../backups/backup3.sql	\N	manual	1	failed	Backup failed: 	2025-12-25 14:05:00.181456
4	backup4.sql	../backups/backup4.sql	49008	manual	1	completed		2025-12-25 14:05:38.963746
\.


--
-- Data for Name: error_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.error_logs (id, error_type, error_message, error_file, error_line, user_id, ip_address, stack_trace, created_at) FROM stdin;
\.


--
-- Data for Name: group_sdgs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.group_sdgs (id, group_id, sdg_id, assigned_at) FROM stdin;
1	233	1	2025-12-23 10:20:42.638243
2	223	2	2025-12-23 10:22:33.665798
\.


--
-- Data for Name: group_thrusts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.group_thrusts (id, group_id, thrust_id, assigned_at) FROM stdin;
1	223	1	2025-12-23 10:22:47.568255
2	223	2	2025-12-23 10:22:47.57636
3	233	4	2025-12-23 12:21:53.991762
4	233	3	2025-12-23 12:21:53.997778
\.


--
-- Data for Name: groups; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.groups (id, name, leader_id, research_title, title_status, advisor_id, adviser_id, sdg_id, thrust_id) FROM stdin;
223	Group1- DIT3	\N	Little Red Riding Hood: Dark Tale	approved	\N	3	\N	\N
233	Group2-DIT3	\N	Off to the Races	approved	\N	7	\N	\N
\.


--
-- Data for Name: programs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.programs (id, code, name, description, is_active, created_at) FROM stdin;
1	BSIT	Bachelor of Science in Information Technology	Bachelor Information Technology Program	t	2025-12-24 13:22:35.287008
2	DIT	Diploma in Information Technology	Diploma Information Technology Program	t	2025-12-24 13:22:35.287008
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
\.


--
-- Data for Name: research_statuses; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.research_statuses (id, name, description, color, is_active, display_order, created_at) FROM stdin;
1	Proposal	Research proposal stage	#007bff	t	1	2025-12-24 13:04:04.781275
2	Chapter 1-3	Working on chapters 1-3	#ffc107	t	2	2025-12-24 13:04:04.781275
3	Chapter 4-5	Working on chapters 4-5	#17a2b8	t	3	2025-12-24 13:04:04.781275
4	Final Defense	Ready for final defense	#28a745	t	4	2025-12-24 13:04:04.781275
5	Completed	Research completed	#6c757d	t	5	2025-12-24 13:04:04.781275
\.


--
-- Data for Name: research_thrusts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.research_thrusts (id, name, description, created_at) FROM stdin;
1	Reliability	\N	2025-12-23 09:55:43.964771
2	Validity	\N	2025-12-23 09:55:58.350714
3	Learnability	\N	2025-12-23 12:21:34.490764
4	Acceptability	\N	2025-12-23 12:21:46.32889
\.


--
-- Data for Name: student; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.student (id, name, school_id, program, pass_word, files, images, group_id, research_title, is_leader, is_active) FROM stdin;
6	Jayson Santollanii	2023-00186-UQ-0	DIT	$2y$10$CxK2Ll8Ue7jhX6DVcqiCo.TWCVJU2ZunKynmF05RFn0rZV1cfGEPq	\N	1766304515_retouch_2024022018394821.jpg	233	\N	f	t
4	Michael L. Nadal	2023-00178-UQ-0	DIT	$2y$10$RDrgJBIz01Umg3VWpVUCleLAStC9Ue8elqjK/X6ekxOB8JTkNzpcm	\N	1763775219_WIN_20250926_09_44_58_Pro.jpg	233	title a	t	t
2	Angeli May April	2023-00179-UQ-0	DIT	$2y$10$OwdcDjLknEqQj91hgYPlEefLW6YFEDKTbv.vU9LknywcKkRV2cruW	\N	1763688682_IMG_20240117_154226.jpg	223	dasfggghggf	t	t
8	Larraine Natalia 	2023-00156-UQ-0	DIT	$2y$10$6uLuEzNmraSdXEZw4npWGOCIuu1zfsIEvkP4.tUWWCe8jf3cCFuz.	\N	\N	223	Three Little Pigs	\N	t
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
\.


--
-- Data for Name: system_notifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.system_notifications (id, notification_type, recipient_type, recipient_id, title, message, priority, status, created_by, sent_at, created_at) FROM stdin;
\.


--
-- Data for Name: system_settings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.system_settings (id, setting_key, setting_value, setting_type, description, updated_by, updated_at, created_at) FROM stdin;
5	backup_frequency	weekly	text	Automatic backup frequency (daily/weekly/monthly)	1	2025-12-25 13:38:52.427663	2025-12-24 16:12:02.037945
4	enable_notifications	true	boolean	Enable email notifications	1	2025-12-25 13:38:52.427663	2025-12-24 16:12:02.037945
6	maintenance_mode	true	boolean	Enable maintenance mode	1	2025-12-25 13:38:52.427663	2025-12-24 16:12:02.037945
2	max_upload_size	10	number	Maximum file upload size in MB	1	2025-12-25 13:38:52.427663	2025-12-24 16:12:02.037945
3	session_timeout	30	number	Session timeout in minutes	1	2025-12-25 13:38:52.427663	2025-12-24 16:12:02.037945
1	site_name	Research Monitoring System	text	System name displayed across the application	1	2025-12-25 13:38:52.427663	2025-12-24 16:12:02.037945
\.


--
-- Data for Name: un_sdgs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.un_sdgs (id, name, description, created_at) FROM stdin;
1	Peace and Justice	\N	2025-12-23 09:52:35.857285
2	Quality Education	\N	2025-12-23 10:22:21.409869
\.


--
-- Data for Name: uploads; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.uploads (upload_id, school_id, task_name, file_path, original_filename, uploaded_at, status, comment) FROM stdin;
1	2023-00178-UQ-0	Chapter 1	uploads/FILE_692112398d1706.11635742.docx	6 PUP Consent Form.docx	2025-11-22 09:30:33.580942	pending	\N
2	2023-00178-UQ-0	Chapter 1	uploads/FILE_6926505895caa1.45358366.docx	is a systematic.docx	2025-11-26 08:56:56.626673	pending	\N
4	2023-00178-UQ-0	Chapter 1	uploads/FILE_693f547d2a0404.04137293.docx	Resume.docx	2025-12-15 08:21:17.184335	pending	\N
7	2023-00178-UQ-0	Chapter 2	uploads/FILE_6947a939d33617.98616598.docx	is a systematic.docx	2025-12-21 16:00:57.86774	rejected	Wrong file
6	2023-00178-UQ-0	Chapter 1	uploads/FILE_694221e9cf33a4.29123121.docx	themeandcitations.docx	2025-12-17 11:22:17.854461	approved	there is nothing wrong here
8	2023-00179-UQ-0	Chapter 1	uploads/FILE_694870b8017728.64274501.docx	Capstone-Template-DIT.docx	2025-12-22 06:12:08.01154	approved	I approved this, but need to polish the DOT
9	2023-00179-UQ-0	Chapter 2	uploads/FILE_6949e7a41558b4.15833730.docx	is a systematic.docx	2025-12-23 08:51:48.092832	pending	\N
10	2023-00179-UQ-0	Chapter 2	uploads/FILE_6949e7b41b9e45.19649550.docx	6 PUP Consent Form.docx	2025-12-23 08:52:04.120885	approved	The spacing needed to be fixed, but approved
11	2023-00178-UQ-0	Chapter 2	uploads/FILE_694cca472cd9e8.42554366.docx	InternsEvaluation.docx	2025-12-25 13:23:19.186892	pending	\N
12	2023-00179-UQ-0	Chapter 3	uploads/FILE_694cd17ecba8b8.69531651.docx	DOCUMENT-SUBMISSION-TEMPLATE_292760372.docx	2025-12-25 13:54:06.83677	pending	\N
\.


--
-- Data for Name: urec_documents; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.urec_documents (id, group_id, school_id, document_type, file_path, original_filename, status, comment, uploaded_at, updated_at) FROM stdin;
2	233	2023-00178-UQ-0	UREC Clearance	uploads/694b8f4997147_1766559561.pdf	ssrn-4101636.pdf	approved	\N	2025-12-24 14:59:21.620528	2025-12-24 14:59:21.620528
1	233	2023-00178-UQ-0	UREC Form	uploads/694b8f3b45870_1766559547.pdf	POLYTECHNIC UNIVERSITY OF THE PHILIPPINES.pdf	approved	goods ig	2025-12-24 14:59:07.288318	2025-12-24 14:59:07.288318
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

SELECT pg_catalog.setval('public.advisor_id_seq', 7, true);


--
-- Name: coordinator_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.coordinator_id_seq', 2, true);


--
-- Name: database_backups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.database_backups_id_seq', 4, true);


--
-- Name: error_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.error_logs_id_seq', 1, false);


--
-- Name: group_sdgs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.group_sdgs_id_seq', 2, true);


--
-- Name: group_thrusts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.group_thrusts_id_seq', 4, true);


--
-- Name: groups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.groups_id_seq', 233, true);


--
-- Name: programs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.programs_id_seq', 2, true);


--
-- Name: progress_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.progress_id_seq', 1, false);


--
-- Name: report_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.report_logs_id_seq', 4, true);


--
-- Name: research_statuses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.research_statuses_id_seq', 5, true);


--
-- Name: research_thrusts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.research_thrusts_id_seq', 4, true);


--
-- Name: student_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.student_id_seq', 8, true);


--
-- Name: system_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.system_logs_id_seq', 14, true);


--
-- Name: system_notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.system_notifications_id_seq', 1, false);


--
-- Name: system_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.system_settings_id_seq', 6, true);


--
-- Name: un_sdgs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.un_sdgs_id_seq', 2, true);


--
-- Name: uploads_upload_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.uploads_upload_id_seq', 12, true);


--
-- Name: urec_documents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.urec_documents_id_seq', 2, true);


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
-- Name: report_logs fk_coordinator; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_logs
    ADD CONSTRAINT fk_coordinator FOREIGN KEY (generated_by) REFERENCES public.coordinator(id);


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
-- Name: urec_documents urec_documents_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.urec_documents
    ADD CONSTRAINT urec_documents_group_id_fkey FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

